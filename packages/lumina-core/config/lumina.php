<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Data Retention
    |--------------------------------------------------------------------------
    |
    | Default number of days raw `events` rows are kept before the
    | `lumina:prune-events` scheduled command deletes them. Individual sites
    | can override this with their `retention_days` column. The anonymous
    | `daily_visitor_stats` aggregates are never pruned, so historical visitor
    | counts survive raw-row deletion.
    |
    */

    'retention_days' => (int) env('LUMINA_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | GeoIP Provider
    |--------------------------------------------------------------------------
    |
    | Country resolution for visitor IPs when no trusted-proxy country header
    | is present. Driver options:
    |
    |   - 'ip-api'  (default) — ip-api.com batch/free HTTP lookup, result cached
    |                           for 24h. The endpoint is configurable so you can
    |                           switch to HTTPS or self-host a compatible
    |                           endpoint.
    |   - 'disabled'          — never perform network lookups; country stays
    |                           null unless supplied via X-Country or a trusted
    |                           proxy header. Fully offline / privacy-maximal.
    |
    | Self-hosters can also provide their own driver by overriding the
    | `CountryResolver` in the container.
    |
    */

    'geoip' => [
        'driver' => env('LUMINA_GEOIP_DRIVER', 'ip-api'),

        'ip_api' => [
            'url' => env('LUMINA_GEOIP_IP_API_URL', 'http://ip-api.com/json/{ip}?fields=countryCode'),
            'timeout' => (int) env('LUMINA_GEOIP_IP_API_TIMEOUT', 2),
        ],
    ],

];
