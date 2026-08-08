<?php

namespace Lumina\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
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

        $cleanPath = parse_url($this->path, PHP_URL_PATH) ?? '/';

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

        Event::create([
            'site_id' => $this->siteId,
            'path' => $this->path,
            'clean_path' => $cleanPath,
            'referrer' => $this->referrer,
            'visitor_hash' => $this->visitorHash,
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
        ]);
    }
}
