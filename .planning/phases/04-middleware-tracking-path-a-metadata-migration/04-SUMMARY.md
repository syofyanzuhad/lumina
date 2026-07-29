# Phase 4 Summary: Middleware Tracking & Metadata Migration

## Accomplishments
- **Task 01 (Metadata Migration & Model):** Created additive migration `2026_07_30_000001_add_metadata_to_events_table.php` adding nullable `metadata` column to `events` table. Updated `Event` model fillable attributes and added `'metadata' => 'array'` cast.
- **Task 02 (DeviceType Enum Parsing):** Implemented `DeviceType::fromUserAgent(string $userAgent)` using lightweight regex pattern matching to categorize requests into `Mobile`, `Tablet`, `Desktop`, or `Unknown` without third-party dependencies.
- **Task 03 (InsertEvent Queued Job):** Created `Lumina\Core\Jobs\InsertEvent` implementing `ShouldQueue`, accepting primitive properties (`siteId`, `path`, `referrer`, `visitorHash`, `deviceType`, `country`, `metadata`) to insert event records safely.
- **Task 04 (Service Provider Updates):** Registered `lumina_ip` (60 req/min per IP) and `lumina_site` (300 req/min per domain) rate limiters in `LuminaCoreServiceProvider` and registered `lumina.track` middleware alias in both `register()` and `boot()`.
- **Task 05 (TrackPageview Middleware):** Created `Lumina\Core\Middleware\TrackPageview` to handle server-side middleware pageview tracking:
  - Resolves `Site` model by host domain (gracefully skips unregistered domains).
  - Enforces rate limiting with silent swallow (proceeds without tracking, avoiding 429 page errors).
  - Hashes visitor IP with daily rotating salt (`lumina_daily_salt_YYYY-MM-DD` stored via SHA-256).
  - Derives country from `X-Country`, `CF-IPCountry`, or `X-Vercel-IP-Country` headers.
  - Dispatches `InsertEvent` job asynchronously.
- **Task 06 (Routing & Feature Tests):** Applied `lumina.track` middleware to routes in `routes/web.php` and added `TrackPageviewMiddlewareTest.php` covering job dispatch, DB insertion, privacy hashing, rate limit fallback, and host validation.

## Key Decisions & Architecture
1. **Silent Rate Limit Swallow:** Middleware tracking on host web routes returns `$next($request)` even when rate limited to avoid breaking user page loading with HTTP 429 errors.
2. **Daily Salt Rotation:** IP privacy hashing uses `sha256($ip . $userAgent . $dailySalt)` where `$dailySalt` key changes daily (`Y-m-d`), preventing cross-day user tracking while maintaining visitor privacy (`PRIV-01` through `PRIV-04`).
3. **Primitive Job Constructor:** `InsertEvent` accepts primitive types (`int $siteId`, etc.) rather than Eloquent models to avoid `ModelNotFoundException` if a site is deleted while job is queued.

## Files Created & Modified
- `packages/lumina-core/database/migrations/2026_07_30_000001_add_metadata_to_events_table.php` (Created)
- `packages/lumina-core/src/Models/Event.php` (Modified)
- `packages/lumina-core/src/Enums/DeviceType.php` (Modified)
- `packages/lumina-core/src/Jobs/InsertEvent.php` (Created)
- `packages/lumina-core/src/LuminaCoreServiceProvider.php` (Modified)
- `packages/lumina-core/src/Middleware/TrackPageview.php` (Created)
- `routes/web.php` (Modified)
- `tests/Feature/TrackPageviewMiddlewareTest.php` (Created)

## Verification Results
- **Laravel Pint:** Executed `vendor/bin/pint packages/lumina-core/src packages/lumina-core/database routes/web.php tests/Feature/TrackPageviewMiddlewareTest.php` — Passed.
- **Feature Tests:** Executed `php artisan test --compact --filter=TrackPageviewMiddlewareTest` — **7/7 passed**.
- **Full Test Suite:** Executed `php artisan test --compact` — **68/68 passed** (0 failures).

## Git Commits
- `f67e027`: `feat(04-01): add metadata migration and event model cast`
- `66c76a0`: `feat(04-02): add fromUserAgent method to DeviceType enum`
- `0c90d77`: `feat(04-03): create InsertEvent queued job`
- `b35183b`: `feat(04-04): register rate limiters and middleware alias in service provider`
- `106246b`: `feat(04-05): create TrackPageview middleware`
- `ec9c7f5`: `feat(04-06): wire lumina.track middleware to web routes and add feature tests`
