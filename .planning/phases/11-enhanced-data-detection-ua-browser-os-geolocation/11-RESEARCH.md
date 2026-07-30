# Research: Phase 11 — Enhanced Data Detection

## Summary
Phase 11 enhances Lumina's analytics collection pipeline with detailed User-Agent (Browser & OS) parsing and Geolocation (Country code & name) detection. The User-Agent string will be parsed using the `jenssegers/agent` PHP library. Geolocation detection will prioritize Cloudflare IP geolocation headers (`CF-IPCountry`) with a cached external HTTP API fallback (`ip-api.com`). Aggregate queries for Browsers, Operating Systems, and Countries will be implemented in `AnalyticsService`, and visually presented in both Vue (Inertia) and Livewire dashboards as Top-5 cards with percentage progress bars.

---

## Existing Code Analysis

### Key Files Identified
- `packages/lumina-core/composer.json`: Core package manifest where `jenssegers/agent` dependency will be added.
- `packages/lumina-core/database/migrations/2026_07_26_111909_create_events_table.php`: Original events table migration containing `device_type` and `country` (2-letter code) columns.
- `packages/lumina-core/src/Models/Event.php`: Eloquent model representing analytics events.
- `packages/lumina-core/src/Jobs/InsertEvent.php`: Queued job responsible for writing events asynchronously to the database. Currently accepts `siteId`, `path`, `referrer`, `visitorHash`, `deviceType`, `country`, and `metadata`.
- `packages/lumina-core/src/Http/Controllers/CollectController.php`: API endpoint (`/api/collect`) processing incoming frontend tracking payloads and dispatching `InsertEvent`.
- `packages/lumina-core/src/Middleware/TrackPageview.php`: Laravel middleware automatically tracking pageviews for server-rendered routes and dispatching `InsertEvent`.
- `packages/lumina-core/src/Services/AnalyticsService.php`: Aggregates metrics (`getPageviews`, `getUniqueVisitors`, `getTopPages`, `getTopReferrers`, `getDeviceBreakdown`, `getCustomEvents`, `getOverview`, `clearCache`).
- `resources/js/pages/Dashboard.vue`: Inertia/Vue dashboard component rendering site analytics cards.
- `packages/lumina-core/src/Livewire/Dashboard.php` & `packages/lumina-core/resources/views/livewire/dashboard.blade.php`: Livewire component and Blade view rendering site analytics.

---

## Database Schema Changes

### Migration Design
Create a new migration: `packages/lumina-core/database/migrations/2026_07_30_000003_add_detection_columns_to_events_table.php`.

```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('browser', 50)->nullable()->after('device_type');
            $table->string('browser_version', 20)->nullable()->after('browser');
            $table->string('os', 50)->nullable()->after('browser_version');
            $table->string('os_version', 20)->nullable()->after('os');
            $table->string('country_code', 2)->nullable()->after('os_version');
            $table->string('country_name', 100)->nullable()->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['browser', 'browser_version', 'os', 'os_version', 'country_code', 'country_name']);
        });
    }
};
```

### Event Model Updates
Update `packages/lumina-core/src/Models/Event.php`:
- Update `#[Fillable]` attribute to include `browser`, `browser_version`, `os`, `os_version`, `country_code`, `country_name`.
- Keep existing `country` column in sync with `country_code` for backward compatibility.

---

## UA Parsing (jenssegers/agent)

### Dependency
Add `"jenssegers/agent": "^2.6"` to `packages/lumina-core/composer.json`.

### API & Normalization Logic
UA parsing will be executed inside `InsertEvent::handle()` (or a dedicated `UserAgentParser` helper service in `Lumina\Core\Support`):

```php
use Jenssegers\Agent\Agent;

$agent = new Agent();
$agent->setUserAgent($userAgent);

$browser = $agent->browser(); // Returns 'Chrome', 'Safari', 'Firefox', 'Edge', etc.
$browserVersion = $browser ? $agent->version($browser) : null;

$os = $agent->platform(); // Returns 'OS X', 'iOS', 'Windows', 'Android', 'Linux', etc.
if ($os === 'OS X') {
    $os = 'macOS'; // Standardize OS X to macOS
}
$osVersion = $os ? $agent->version($os) : null;
```

### Edge Cases
- **Bots / Curl / Unknown User-Agents**: `$agent->browser()` or `$agent->platform()` may return `false` or empty string. Handle with `$browser ?: null` and `$os ?: null` or fallback to `'Other'`.
- **Version Truncation**: Limit browser version string lengths if necessary (e.g. `'126.0'`).

---

## GeoIP Detection

### Resolution Workflow
1. **Cloudflare / Request Headers (Primary)**:
   Extract `$request->header('CF-IPCountry') ?? $request->header('X-Country') ?? $request->header('X-Vercel-IP-Country')`.
   If valid ISO 2-letter country code (e.g. `'US'`, `'ID'`), set `$countryCode`.

2. **Country Code to Country Name Lookup**:
   Create `Lumina\Core\Support\CountryHelper` containing an array mapping of ISO alpha-2 country codes to full country names (e.g. `'US' => 'United States'`, `'ID' => 'Indonesia'`, `'GB' => 'United Kingdom'`, etc.).
   Resolving country name from header code is an instant in-memory array lookup (<0.01ms).

3. **External API Fallback (`ip-api.com`)**:
   If `$countryCode` is missing or null:
   - Check if IP is a public IP (`FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE`). Skip for private/localhost IPs (`127.0.0.1`, `10.0.0.0/8`, `192.168.0.0/16`).
   - Use `Cache::remember("geoip:{$ip}", 86400, ...)` to query `http://ip-api.com/json/{$ip}?fields=status,country,countryCode`.
   - Set strict timeout via `Http::timeout(2)`.
   - If HTTP request fails or times out, swallow exception gracefully and return `null` for both `country_code` and `country_name`.

---

## AnalyticsService Changes

### New Aggregate Methods
Add the following methods to `Lumina\Core\Services\AnalyticsService`:

```php
/**
 * Get top browsers for site and date range.
 */
public function getTopBrowsers(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 5): Collection
{
    $cacheKey = $this->cacheKey($site->id, "top_browsers_{$limit}", $start, $end);

    $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit) {
        $totalPageviews = $this->getPageviews($site, $start, $end);

        $results = Event::where('site_id', $site->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('browser')
            ->where('browser', '!=', '')
            ->select('browser', DB::raw('count(*) as count'))
            ->groupBy('browser')
            ->orderByDesc('count')
            ->orderBy('browser')
            ->limit($limit)
            ->get();

        return $results->map(function ($row) use ($totalPageviews) {
            $count = (int) $row->count;

            return [
                'browser' => (string) $row->browser,
                'count' => $count,
                'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
            ];
        })->toArray();
    });

    return collect($data ?? []);
}

/**
 * Get top operating systems for site and date range.
 */
public function getTopOperatingSystems(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 5): Collection
{
    $cacheKey = $this->cacheKey($site->id, "top_os_{$limit}", $start, $end);

    $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit) {
        $totalPageviews = $this->getPageviews($site, $start, $end);

        $results = Event::where('site_id', $site->id)
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('os')
            ->where('os', '!=', '')
            ->select('os', DB::raw('count(*) as count'))
            ->groupBy('os')
            ->orderByDesc('count')
            ->orderBy('os')
            ->limit($limit)
            ->get();

        return $results->map(function ($row) use ($totalPageviews) {
            $count = (int) $row->count;

            return [
                'os' => (string) $row->os,
                'count' => $count,
                'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
            ];
        })->toArray();
    });

    return collect($data ?? []);
}

/**
 * Get top countries for site and date range.
 */
public function getTopCountries(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 5): Collection
{
    $cacheKey = $this->cacheKey($site->id, "top_countries_{$limit}", $start, $end);

    $data = Cache::remember($cacheKey, $this->ttl, function () use ($site, $start, $end, $limit) {
        $totalPageviews = $this->getPageviews($site, $start, $end);

        $results = Event::where('site_id', $site->id)
            ->whereBetween('created_at', [$start, $end])
            ->where(function ($query) {
                $query->whereNotNull('country_name')
                    ->orWhereNotNull('country_code')
                    ->orWhereNotNull('country');
            })
            ->select(
                DB::raw('COALESCE(country_name, country_code, country) as country_name'),
                DB::raw('COALESCE(country_code, country) as country_code'),
                DB::raw('count(*) as count')
            )
            ->groupBy(DB::raw('COALESCE(country_name, country_code, country)'), DB::raw('COALESCE(country_code, country)'))
            ->orderByDesc('count')
            ->limit($limit)
            ->get();

        return $results->map(function ($row) use ($totalPageviews) {
            $count = (int) $row->count;
            $code = (string) ($row->country_code ?? '');
            $name = (string) ($row->country_name ?? $code);

            return [
                'country_code' => $code,
                'country_name' => $name,
                'count' => $count,
                'percentage' => $totalPageviews > 0 ? round(($count / $totalPageviews) * 100, 1) : 0.0,
            ];
        })->toArray();
    });

    return collect($data ?? []);
}
```

### Overview & Cache Invalidation
- `getOverview()`: Include `'top_browsers'`, `'top_os'`, `'top_countries'`.
- `clearCache()`: Forget `top_browsers_5`, `top_os_5`, `top_countries_5` keys.

---

## Dashboard Integration

### Inertia / Vue (`resources/js/pages/Dashboard.vue`)
- Update `Overview` TypeScript interface to include `top_browsers?: TopBrowser[]`, `top_os?: TopOS[]`, `top_countries?: TopCountry[]`.
- Add Top Browsers, Top Operating Systems, and Top Locations cards to the details grid section with percentage progress bars.

### Livewire (`packages/lumina-core/resources/views/livewire/dashboard.blade.php`)
- Add cards for Top Browsers, Top Operating Systems, and Top Countries in the Livewire dashboard template, matching existing styling and percentage progress bar layout.

---

## Implementation Risks

1. **External API Rate Limits (`ip-api.com`)**:
   Free tier has a rate limit of 45 requests per minute per IP.
   *Mitigation*: Primary detection relies on Cloudflare header (`CF-IPCountry`). IP lookups are cached for 24 hours (`Cache::remember`), and API calls skip private/local IPs.
2. **Queue Processing Time (<2ms Overhead Constraint)**:
   Running UA parsing and GeoIP detection synchronously during the HTTP request would violate SLA.
   *Mitigation*: All parsing and GeoIP lookups happen inside the async `InsertEvent` queue job. Within the job, in-memory UA parsing takes <0.2ms and header-based country lookup takes <0.01ms.

---

## Validation Architecture

- **Unit Tests**:
  - `UserAgentParserTest`: Test UA string parsing for Chrome, Safari, Firefox, Edge, mobile/desktop OS strings, and bot/curl fallbacks.
  - `CountryHelperTest`: Test country code to name mapping logic.
- **Feature Tests**:
  - `InsertEventJobTest`: Verify `InsertEvent` job parses UA and resolves country code/name during handle.
  - `CollectEndpointTest` & `TrackPageviewMiddlewareTest`: Verify `userAgent` and `ip` are passed into `InsertEvent` job dispatch.
  - `AnalyticsServiceTest`: Verify `getTopBrowsers`, `getTopOperatingSystems`, and `getTopCountries` aggregate calculations.
  - `DashboardControllerTest` & `LivewireDashboardTest`: Verify dashboard responses include `top_browsers`, `top_os`, `top_countries`.

---

## Files to Modify

1. `packages/lumina-core/composer.json`: Add `jenssegers/agent` dependency.
2. `packages/lumina-core/database/migrations/2026_07_30_000003_add_detection_columns_to_events_table.php`: Create new migration for `browser`, `browser_version`, `os`, `os_version`, `country_code`, `country_name`.
3. `packages/lumina-core/src/Models/Event.php`: Update `$fillable` array.
4. `packages/lumina-core/database/factories/EventFactory.php`: Update factory attributes for new detection columns.
5. `packages/lumina-core/src/Support/CountryHelper.php` (new): ISO country code to country name dictionary.
6. `packages/lumina-core/src/Jobs/InsertEvent.php`: Update job parameters and handle method to parse User-Agent and resolve Geolocation.
7. `packages/lumina-core/src/Http/Controllers/CollectController.php`: Pass `userAgent` and `ip` into `InsertEvent` dispatch.
8. `packages/lumina-core/src/Middleware/TrackPageview.php`: Pass `userAgent` and `ip` into `InsertEvent` dispatch.
9. `packages/lumina-core/src/Services/AnalyticsService.php`: Add `getTopBrowsers`, `getTopOperatingSystems`, `getTopCountries` methods, update `getOverview()` and `clearCache()`.
10. `resources/js/pages/Dashboard.vue`: Add Browser, OS, and Location breakdown cards with progress bars.
11. `packages/lumina-core/resources/views/livewire/dashboard.blade.php`: Add Browser, OS, and Location breakdown cards with progress bars.
12. `tests/Feature/QueueWorkerIntegrationTest.php`: Update tests for `InsertEvent` with new columns.
13. `tests/Feature/DashboardControllerTest.php`: Assert Inertia page props contain `top_browsers`, `top_os`, `top_countries`.

## RESEARCH COMPLETE
