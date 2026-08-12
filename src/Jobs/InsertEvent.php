<?php

namespace Lumina\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Jenssegers\Agent\Agent;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;
use Lumina\Core\Support\CountryHelper;
use Lumina\Core\Support\CountryResolver;

class InsertEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>|null  $metadata
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
            if (is_string($b) && $b !== '') {
                $browser = $b;
                $bv = $agent->version($b);
                $browserVersion = $bv !== false && $bv !== '' ? (string) $bv : null;
            }

            $platform = $agent->platform();
            if (is_string($platform) && $platform !== '') {
                $os = $platform;
                $ov = $agent->version($platform);
                $osVersion = $ov !== false && $ov !== '' ? (string) $ov : null;
            }
        }

        $countryCode = $this->country;

        // GeoIP resolution is pluggable (see lumina.geoip.driver) and only runs
        // when no country override was provided. A disabled driver performs no
        // network calls at all, keeping self-hosted deployments fully offline.
        if (! $countryCode) {
            $countryCode = app(CountryResolver::class)->resolve($this->ip);
        }

        $countryName = CountryHelper::getName($countryCode);

        // Bound the path to the column width (varchar 255) so an over-long
        // middleware-tracked URL can never fail the insert. The collect
        // endpoint additionally validates max:255, making this the single
        // choke point for both ingestion paths.
        $path = Str::limit($this->path, 255, '');

        $cleanPath = parse_url($path, PHP_URL_PATH) ?: '/';

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
            $utmSource = $this->utmValue($queryParams, 'utm_source');
            $utmMedium = $this->utmValue($queryParams, 'utm_medium');
            $utmCampaign = $this->utmValue($queryParams, 'utm_campaign');
            $utmTerm = $this->utmValue($queryParams, 'utm_term');
            $utmContent = $this->utmValue($queryParams, 'utm_content');
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
     * Extract a scalar UTM parameter value, bounded to the column width.
     *
     * parse_str() can produce nested arrays (e.g. `utm_source[]=x`); only
     * scalar values are ever stored, so the JSON-ish array form is discarded.
     *
     * @param  array<int|string, array<mixed>|string>  $queryParams
     */
    protected function utmValue(array $queryParams, string $key): ?string
    {
        $value = $queryParams[$key] ?? null;

        return is_scalar($value) ? substr((string) $value, 0, 255) : null;
    }

    /**
     * Maintain the daily_visitor_stats aggregate with a portable upsert that
     * works on both MySQL (ON DUPLICATE KEY UPDATE) and SQLite (ON CONFLICT).
     *
     * The aggregate is keyed by the *resolved* identity (visitor_id when a
     * client-provided opaque ID exists, otherwise the fallback hash) — the
     * same COALESCE(visitor_id, visitor_hash) the analytics queries use — so
     * a mixed JS/non-JS population can never double-count the same visitor
     * across the two tables.
     */
    protected function recordDailyVisitorStats(Event $event): void
    {
        $date = $event->created_at->toDateString();
        $visitorKey = $this->visitorId ?? $this->visitorHash;

        DB::table('daily_visitor_stats')->upsert(
            [
                'site_id' => $this->siteId,
                'date' => $date,
                'visitor_hash' => $visitorKey,
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
