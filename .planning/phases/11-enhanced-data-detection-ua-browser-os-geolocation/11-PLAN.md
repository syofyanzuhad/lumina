---
phase: 11
plan: 1
type: feature
wave: 1
depends_on: []
files_modified:
  - packages/lumina-core/composer.json
  - packages/lumina-core/database/migrations/2026_07_30_000003_add_detection_columns_to_events_table.php
  - packages/lumina-core/src/Models/Event.php
  - packages/lumina-core/database/factories/EventFactory.php
  - packages/lumina-core/src/Support/CountryHelper.php
  - packages/lumina-core/src/Jobs/InsertEvent.php
  - packages/lumina-core/src/Http/Controllers/CollectController.php
  - packages/lumina-core/src/Middleware/TrackPageview.php
  - packages/lumina-core/src/Services/AnalyticsService.php
  - resources/js/pages/Dashboard.vue
  - packages/lumina-core/resources/views/livewire/dashboard.blade.php
autonomous: true
requirements: [REQ-DETECT-01, REQ-DETECT-02, REQ-DETECT-03, REQ-DETECT-04]
---

# Plan 11.1 — Enhanced Data Detection (UA Browser/OS & Geolocation)

<objective>
Parse User-Agent for Browser & OS versioning and resolve GeoIP detection (country code & country name) with aggregation and dashboard rendering in both Vue and Livewire.
</objective>

<tasks>
## Task 1: Add jenssegers/agent dependency
- **type**: command
- **files**: `packages/lumina-core/composer.json`
- **action**: 
  - Change directory to `packages/lumina-core`
  - Run `composer require jenssegers/agent:"^2.6"` to add the dependency to `packages/lumina-core/composer.json`.
- **verify**: Check that `packages/lumina-core/composer.json` has `"jenssegers/agent": "^2.6"` in the require section and `composer.lock` is updated.
- **acceptance_criteria**: Composer package installs successfully.

## Task 2: Create migration for new columns
- **type**: migration
- **files**: `packages/lumina-core/database/migrations/2026_07_30_000003_add_detection_columns_to_events_table.php`
- **action**: Create a new migration file with `Schema::table('events', function (Blueprint $table) { ... })` adding:
  - `$table->string('browser')->nullable();`
  - `$table->string('browser_version')->nullable();`
  - `$table->string('os')->nullable();`
  - `$table->string('os_version')->nullable();`
  - `$table->string('country_code', 2)->nullable();`
  - `$table->string('country_name')->nullable();`
  And a down method to drop these columns.
- **verify**: Run `php artisan migrate` to ensure the migration applies cleanly.
- **acceptance_criteria**: Events table has the 6 new nullable columns.

## Task 3: Update Event model $fillable
- **type**: code
- **files**: `packages/lumina-core/src/Models/Event.php`
- **action**: Add `'browser'`, `'browser_version'`, `'os'`, `'os_version'`, `'country_code'`, `'country_name'` to the `#[Fillable]` attribute or `$fillable` array in the `Event` model.
- **verify**: Check `packages/lumina-core/src/Models/Event.php`.
- **acceptance_criteria**: `Event` model allows mass assignment for the new columns.

## Task 4: Update EventFactory
- **type**: code
- **files**: `packages/lumina-core/database/factories/EventFactory.php`
- **action**: Add fake data for the new columns to the `definition()` method:
  ```php
  'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
  'browser_version' => $this->faker->numberBetween(80, 120) . '.0',
  'os' => $this->faker->randomElement(['Windows', 'macOS', 'iOS', 'Android']),
  'os_version' => $this->faker->numberBetween(10, 17) . '.0',
  'country_code' => $this->faker->countryCode(),
  'country_name' => $this->faker->country(),
  ```
- **verify**: Inspect the file.
- **acceptance_criteria**: Factory successfully generates mock data for the new columns.

## Task 5: Create CountryHelper
- **type**: code
- **files**: `packages/lumina-core/src/Support/CountryHelper.php`
- **action**: Create a static class `Lumina\Core\Support\CountryHelper` with a method `public static function getName(?string $code): ?string` which returns a country name from an array mapping of ISO 3166-1 alpha-2 codes (at least 50+ common countries, like 'US' => 'United States', 'GB' => 'United Kingdom', etc.).
- **verify**: Verify class exists and returns names correctly.
- **acceptance_criteria**: `CountryHelper::getName('US')` returns `'United States'`.

## Task 6: Update InsertEvent Job
- **type**: code
- **files**: `packages/lumina-core/src/Jobs/InsertEvent.php`
- **action**: 
  - Add `public ?string $userAgent = null,` and `public ?string $ip = null,` to the constructor.
  - In `handle()`, use `\Jenssegers\Agent\Agent` to parse `$this->userAgent`:
    ```php
    $agent = new \Jenssegers\Agent\Agent();
    $agent->setUserAgent($this->userAgent);
    $browser = $agent->browser();
    $browserVersion = $agent->version($browser);
    $os = $agent->platform();
    $osVersion = $agent->version($os);
    ```
  - For GeoIP, if `$this->country` (which comes from headers) is missing, and `$this->ip` is present (and not a private IP), try to fetch from `ip-api.com` with `Http::timeout(2)` and `Cache::remember('geoip:'.$this->ip, 86400, ...)`.
  - Save all extracted data (including `CountryHelper::getName($countryCode)`) to `Event::create(...)`.
- **verify**: Run a test or inspect the file visually.
- **acceptance_criteria**: `InsertEvent` handles UA parsing and GeoIP resolution and saves the 6 new columns.

## Task 7: Update CollectController
- **type**: code
- **files**: `packages/lumina-core/src/Http/Controllers/CollectController.php`
- **action**: Pass `userAgent: $userAgent` and `ip: $request->ip()` to `InsertEvent::dispatch(...)` in the `__invoke` method.
- **verify**: Check `CollectController.php`.
- **acceptance_criteria**: `userAgent` and `ip` are passed to `InsertEvent`.

## Task 8: Update TrackPageview Middleware
- **type**: code
- **files**: `packages/lumina-core/src/Middleware/TrackPageview.php`
- **action**: Pass `userAgent: $userAgent` and `ip: $request->ip()` to `InsertEvent::dispatch(...)` in the `handle` method.
- **verify**: Check `TrackPageview.php`.
- **acceptance_criteria**: `userAgent` and `ip` are passed to `InsertEvent`.

## Task 9: Update AnalyticsService
- **type**: code
- **files**: `packages/lumina-core/src/Services/AnalyticsService.php`
- **action**: 
  - Add `getTopBrowsers`, `getTopOperatingSystems`, `getTopCountries` methods (similar to `getTopPages`), caching for `$this->ttl`, limiting to `$limit = 10`. Return percentage and counts.
  - Update `getOverview()` to merge these 3 new methods into the returned array keys: `top_browsers`, `top_os`, `top_countries`.
  - Update `clearCache()` to remove these keys.
- **verify**: Inspect `AnalyticsService.php`.
- **acceptance_criteria**: Aggregates for browser, OS, and country are computed and cached.

## Task 10: Update Dashboard.vue
- **type**: code
- **files**: `resources/js/pages/Dashboard.vue`
- **action**: 
  - Extend the `Overview` interface to include `top_browsers`, `top_os`, `top_countries` (using a similar type to `TopPage`).
  - Below the existing breakdown cards (Top Pages, Referrers, Devices), add three new cards for Top Browsers, Top OS, Top Locations. Each displaying list of items with progress bars (colored appropriately, matching existing aesthetics).
- **verify**: Ensure Vue compiles without TS errors (`npm run build`).
- **acceptance_criteria**: Dashboard renders the new aggregate cards.

## Task 11: Update Livewire Dashboard
- **type**: code
- **files**: `packages/lumina-core/resources/views/livewire/dashboard.blade.php`
- **action**: Add matching sections for Top Browsers, Top OS, and Top Countries in the Livewire view, structured similarly to the Top Pages section. Use Tailwind colored progress bars.
- **verify**: Check Blade view for syntax.
- **acceptance_criteria**: Livewire dashboard renders the new aggregate cards.

## Task 12: Write Tests
- **type**: test
- **files**: `tests/Feature/AnalyticsTest.php` or create a new test file.
- **action**: Write Pest tests verifying that `InsertEvent` creates events with correct browser/os/country, and `AnalyticsService` returns the correct grouped totals for them. Verify `CollectController` response.
- **verify**: Run `php artisan test --compact`.
- **acceptance_criteria**: Tests pass.
</tasks>

<verification>
Run `php artisan test --compact` to verify everything works and all tests pass. Make a manual API call to `CollectController` with a spoofed User-Agent and check the DB manually.
</verification>

<success_criteria>
- REQ-DETECT-01: UA parsing extracts Browser name/version + OS name/version
- REQ-DETECT-02: Event ingest extracts Country code/name, stored with event
- REQ-DETECT-03: AnalyticsService provides top Browsers, top OS, top Countries aggregates
- REQ-DETECT-04: Both dashboards render Browser, OS, Location breakdown cards
</success_criteria>
