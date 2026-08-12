<?php

namespace Lumina\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;
use Lumina\Core\Support\CountryHelper;

class InsertEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $siteId,
        public string $path,
        public ?string $referrer,
        public string $visitorHash,
        public DeviceType|string $deviceType,
        public ?string $country = null,
        public ?array $metadata = null,
        public ?string $userAgent = null,
        public ?string $ip = null,
        public ?string $visitorId = null,
        public ?string $sessionId = null,
        public ?string $eventId = null,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Referrer truncation is handled here (single choke point for both the
        // middleware and the collect endpoint) so an over-long referrer can
        // never fail the insert.
        $referrer = $this->referrer !== null
            ? Str::limit($this->referrer, 255, '')
            : null;

        $browser = null;
        $browserVersion = null;
        $os = null;
        $osVersion = null;

        if ($this->userAgent) {
            $agent = new Agent;
            $agent->setUserAgent($this->userAgent);

            $b = $agent->browser();
            if ($b) {
                $browser = $b;
                $bv = $agent->version($b);
                $browserVersion = $bv !== false && $bv !== '' ? (string) $bv : null;
            }

            $platform = $agent->platform();
            if ($platform) {
                $os = $platform;
                $ov = $agent->version($platform);
                $osVersion = $ov !== false && $ov !== '' ? (string) $ov : null;
            }
        }

        $countryCode = $this->country;

        if (! $countryCode && $this->ip && filter_var($this->ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            $countryCode = Cache::remember('geoip:'.$this->ip, 86400, function () {
                try {
                    $response = Http::timeout(2)->get("http://ip-api.com/json/{$this->ip}?fields=countryCode");
                    if ($response->successful() && isset($response->json()['countryCode'])) {
                        return $response->json()['countryCode'];
                    }
                } catch (\Throwable $e) {
                    // Ignore lookup failure
                }

                return null;
            });
        }

        $countryName = CountryHelper::getName($countryCode);

        // Bound the path to the column width (varchar 255) so an over-long
        // middleware-tracked URL can never fail the insert. The collect
        // endpoint additionally validates max:255, making this the single
        // choke point for both ingestion paths.
        $path = Str::limit($this->path, 255, '');

        $cleanPath = parse_url($path, PHP_URL_PATH) ?? '/';

        $cleanPath = '/'.ltrim($cleanPath, '/');

        // Extract UTM parameters if query string is present
        $queryString = parse_url($this->path, PHP_URL_QUERY);
        $utmSource = null;
        $utmMedium = null;
        $utmCampaign = null;
        $utmTerm = null;
        $utmContent = null;

        if ($queryString) {
            parse_str($queryString, $queryParams);
            $utmSource = isset($queryParams['utm_source']) ? substr((string) $queryParams['utm_source'], 0, 255) : null;
            $utmMedium = isset($queryParams['utm_medium']) ? substr((string) $queryParams['utm_medium'], 0, 255) : null;
            $utmCampaign = isset($queryParams['utm_campaign']) ? substr((string) $queryParams['utm_campaign'], 0, 255) : null;
            $utmTerm = isset($queryParams['utm_term']) ? substr((string) $queryParams['utm_term'], 0, 255) : null;
            $utmContent = isset($queryParams['utm_content']) ? substr((string) $queryParams['utm_content'], 0, 255) : null;
        }

        $attributes = [
            'site_id' => $this->siteId,
            'path' => $path,
            'clean_path' => $cleanPath,
            'referrer' => $referrer,
            'visitor_hash' => $this->visitorHash,
            'visitor_id' => $this->visitorId ?? $this->visitorHash,
            'session_id' => $this->sessionId,
            'event_id' => $this->eventId,
            'device_type' => $this->deviceType,
            'country' => $countryCode,
            'browser' => $browser,
            'browser_version' => $browserVersion,
            'os' => $os,
            'os_version' => $osVersion,
            'country_code' => $countryCode,
            'country_name' => $countryName,
            'utm_source' => $utmSource,
            'utm_medium' => $utmMedium,
            'utm_campaign' => $utmCampaign,
            'utm_term' => $utmTerm,
            'utm_content' => $utmContent,
            'metadata' => $this->metadata,
        ];

        // Idempotent insertion: an event_id generated at tracking time makes
        // queue retries safe — a duplicate event_id is simply ignored, so
        // retried jobs can never double-count pageviews. createOrFirst() is
        // race-safe under concurrent workers (firstOrCreate would let a second
        // worker race past the SELECT and hit the unique index).
        if ($this->eventId !== null) {
            $event = Event::query()->createOrFirst(
                ['event_id' => $this->eventId],
                $attributes
            );

            if (! $event->wasRecentlyCreated) {
                return;
            }
        } else {
            $event = Event::create($attributes);
        }

        $this->recordDailyVisitorStats($event);
    }

    /**
     * Maintain the daily_visitor_stats aggregate with a portable upsert that
     * works on both MySQL (ON DUPLICATE KEY UPDATE) and SQLite (ON CONFLICT).
     */
    protected function recordDailyVisitorStats(Event $event): void
    {
        $date = $event->created_at->toDateString();

        DB::table('daily_visitor_stats')->upsert(
            [
                'site_id' => $this->siteId,
                'date' => $date,
                'visitor_hash' => $this->visitorHash,
                'views' => 1,
                'created_at' => $event->created_at,
                'updated_at' => $event->created_at,
            ],
            ['site_id', 'date', 'visitor_hash'],
            [
                'views' => DB::raw('views + 1'),
                'updated_at' => $event->created_at,
            ]
        );
    }
}
