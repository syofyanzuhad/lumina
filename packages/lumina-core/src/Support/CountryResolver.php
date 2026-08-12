<?php

namespace Lumina\Core\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Resolves a visitor's country code from their IP address.
 *
 * The provider is pluggable via `config('lumina.geoip.driver')` so self-hosted
 * deployments never depend on a third-party service:
 *
 *   - 'ip-api'  (default) — cached HTTP lookup against a configurable endpoint.
 *   - 'disabled'          — no network calls at all; country stays null unless
 *                           supplied via X-Country or a trusted proxy header.
 *
 * Private/reserved IPs are never looked up, and lookup failures are swallowed —
 * country is derived, never identity, so a miss degrades to "unknown" rather
 * than failing tracking.
 */
class CountryResolver
{
    /**
     * Resolve the ISO 3166-1 alpha-2 country code for an IP, or null.
     */
    public function resolve(?string $ip): ?string
    {
        if ($ip === null || $ip === '' || ! $this->isPublicIp($ip)) {
            return null;
        }

        if (config('lumina.geoip.driver', 'ip-api') === 'disabled') {
            return null;
        }

        return Cache::remember('geoip:'.$ip, 86400, fn (): ?string => $this->lookup($ip));
    }

    /**
     * Perform the actual provider lookup.
     */
    protected function lookup(string $ip): ?string
    {
        $url = str_replace(
            '{ip}',
            $ip,
            (string) config('lumina.geoip.ip_api.url', 'http://ip-api.com/json/{ip}?fields=countryCode')
        );

        try {
            $response = Http::timeout((int) config('lumina.geoip.ip_api.timeout', 2))->get($url);

            if ($response->successful() && isset($response->json()['countryCode'])) {
                return (string) $response->json()['countryCode'];
            }
        } catch (\Throwable) {
            // Ignore lookup failures: country stays null, tracking continues.
        }

        return null;
    }

    /**
     * Whether an IP is a publicly routable address (not private/reserved).
     */
    protected function isPublicIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) !== false;
    }
}
