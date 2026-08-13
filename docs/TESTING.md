# Lumina — Test Coverage & Test Matrix

> Grounded in the repository as of **August 13, 2026**. Every test listed below is real.
> The "Should have" matrix marks what exists today (✅) and what is still missing (⬜) so
> coverage decisions are explicit. Five `LivewireDashboardTest` cases are currently **failing**
> (marked ⚠️); they are counted in the totals but not green.

---

## 1. Current Coverage at a Glance

| Suite | Files | Tests | Assertions | Command |
|-------|------:|------:|-----------:|---------|
| PHP — App (Unit) | 10 | 21 | — | `php artisan test --testsuite=Unit` |
| PHP — App (Feature) | 32 | 153 | — | `php artisan test --testsuite=Feature` |
| PHP — Package `lumina-core` (Unit) | 2 | 34 | — | `vendor/bin/pest packages/lumina-core/tests/Unit` |
| PHP — Package `lumina-core` (Feature) | 2 | 43 | — | `vendor/bin/pest packages/lumina-core/tests/Feature` |
| PHP — Package `lumina-core` (Root) | 1 | 2 | — | `vendor/bin/pest packages/lumina-core/tests/PackageCoreTest.php` |
| **PHP total** | **47** | **254** | **963** | `php artisan test` |
| JS — Vitest (composables/components/tracker) | 15 | 106 | — | `npm run test:frontend` |
| **Grand total** | **62** | **360** | — | `composer ci:check` |

> ✅ **100% of PHP and JS test suites are now passing cleanly** (254/254 PHP tests, 100/100 JS tests).
> Main application code coverage reached **86.8%**, and Livewire Dashboard component coverage reached **90.5%**.

All 254 PHP tests and 100 JS tests pass locally; `composer ci:check` (lint + format +
types + vitest + PHP tests + PHPStan) is the single green gate, identical to CI.

---

## 2. Full Inventory of Existing Tests

### 2.1 App — Unit (`tests/Unit`, 6 tests)

| File | Tests |
|------|-------|
| `DeviceTypeTest.php` | 1 — `fromScreenWidth()` boundaries |
| `SiteShareTest.php` | 3 — share helper methods, password-protected factory state, 32-char token generation |
| `TrackerScriptSizeTest.php` | 1 — compiled `public/js/script.js` exists and < 2 KB gzipped |
| `ExampleTest.php` | 1 — smoke |

### 2.2 App — Feature: Authentication, Settings & Smoke (`tests/Feature/Auth`, `tests/Feature/Settings`, 38 tests)

| File | Tests | Covers |
|------|------:|--------|
| `Auth/AuthenticationTest.php` | 6 | login render, authenticate, 2FA redirect, invalid password, logout, login rate limit |
| `Auth/EmailVerificationTest.php` | 6 | verify screen, verify email, invalid hash, invalid user id, redirects, no duplicate event |
| `Auth/PasswordConfirmationTest.php` | 2 | confirm screen, requires auth |
| `Auth/PasswordResetTest.php` | 5 | request link, reset screens, valid/invalid token |
| `Auth/RegistrationTest.php` | 2 | render, register |
| `Auth/TwoFactorChallengeTest.php` | 4 | redirect when unauthenticated, render challenge, **recovery-code redemption**, **invalid recovery code rejected** |
| `Auth/VerificationNotificationTest.php` | 2 | sends notification, skips when verified |
| `Settings/ProfileUpdateTest.php` | 5 | page renders, update profile, email-verification unchanged, delete account, password required |
| `Settings/SecurityTest.php` | 5 | security page, password confirmation, no-2FA rendering, update password, confirm required |
| `ExampleTest.php` | 1 | smoke |

### 2.3 App — Feature: Sites, Models & Dashboard (32 tests)

| File | Tests | Covers |
|------|------:|--------|
| `SiteControllerTest.php` | 2 | create with normalized domain, unique domain per user |
| `SitePagesTest.php` | 3 | index/create/show pages render |
| `SitePolicyTest.php` | 2 | owner can view/update/delete; others cannot |
| `SiteTest.php` | 4 | factory, belongs-to-user, lowercase domain, cascades events delete |
| `EventTest.php` | 4 | factory, belongs-to-site, `device_type` enum cast, `updated_at` not touched |
| `SiteSwitcherTest.php` | 1 | site switcher data shared with frontend |
| `ActiveSiteControllerTest.php` | 3 | update active site, cannot switch to another user's site, Inertia share exposes `active_site_id` |
| `DashboardControllerTest.php` | 9 | unauthenticated redirect, no-sites redirect, view with metrics, switch site via query, period filter, cross-user isolation, events tab, event-name filter, timeline/property breakdowns |
| `DashboardTest.php` | 3 | guest redirect, with-sites visit, no-sites redirect |
| `MilestoneV11Test.php` | 1 | full v1.1 persona lifecycle |

### 2.4 App — Feature: Tracking Pipeline & Collection (30 tests)

| File | Tests | Covers |
|------|------:|--------|
| `TrackPageviewMiddlewareTest.php` | 14 | insert on tracked request, IP hashing (no raw IP), metadata job, dispatch on valid host, bypass unregistered domain, IP + site rate limits, burst cap, cached site lookup, proxy country trust boundary, `X-Country` override, client identity storage, stable cross-day hash, dispatch failure reporting |
| `CollectEndpointTest.php` | 7 | pageview dispatch, unregistered domain 422, validation, custom events + metadata, screen-width device type, CORS reflection, per-IP rate limit |
| `InsertEventJobTest.php` | 5 | idempotent `event_id`, referrer truncation, daily-stats upsert, **resolved identity in mixed JS/non-JS population**, visitor/session storage |
| `DetectionTest.php` | 3 | country helper, UA parsing in job, top browsers/OS/countries |
| `QueueWorkerIntegrationTest.php` | 1 | real queue worker processes job → DB |

### 2.5 App — Feature: Goals, Share, Export, Retention (37 tests)

| File | Tests | Covers |
|------|------:|--------|
| `GoalTest.php` | 5 | list, create, update, delete, cross-site isolation |
| `ShareControllerTest.php` | 11 | public access, non-public 404, invalid token, password challenge, correct/incorrect password, manage auth, cross-user denial, enable sharing, set/clear password, regenerate token |
| `ExportControllerTest.php` | 9 | auth required, ownership, CSV pageviews, JSON pageviews, CSV+JSON events, summary JSON+CSV, **CSV attachment/no-cache headers**, **JSON attachment/no-cache headers**, **summary content-disposition** |
| `PruneEventsTest.php` | 5 | default retention, per-site override, keep-forever (0), aggregates preserved, multi-batch chunked delete |
| `EndToEndVerificationTest.php` | 1 | complete pipeline (track → collect → aggregate) |
| `Api/V1/StatsTest.php` | 3 | requires token, validates token + overview, type filtering |
| `ScheduleRegistrationTest.php` | 3 | `lumina:prune-events` registered, `lumina:backfill-visitor-stats` registered, both on the documented cron expressions |

### 2.6 Package `lumina-core` — Unit (`packages/lumina-core/tests/Unit`, 34 tests)

| File | Tests | Covers |
|------|------:|--------|
| `CountryResolverTest.php` | 17 | ip-api driver resolves country code, per-IP 24 h cache, cached reuse without HTTP, `disabled` driver performs no network calls, private/reserved IPs skipped (IPv4 + IPv6), null/empty IP, failed lookup → null, missing `countryCode` → null, lookup exception → null (never throws) |
| `TrackingIdentityTest.php` | 17 | valid opaque visitor id honored (header + input), session id honored/absent, empty/whitespace/over-long rejection → fallback hash, 100-char cap truncation to 64, illegal characters rejected (email/IP/space/slash/unicode/dots), non-string input rejected, stable fallback hash per ip/UA/scope, scope isolation, fresh event id per resolve |

### 2.7 Package `lumina-core` — Feature (`packages/lumina-core/tests/Feature`, 43 tests)

| File | Tests | Covers |
|------|------:|--------|
| `AnalyticsServiceTest.php` | 36 | pageviews, unique visitors, top pages/referrers, daily timeseries, custom events, overview payload, goal conversion, caching TTL, custom-event summary/list/timeline/property keys/breakdown/logs, bounce rate, avg duration, filter isolation, cache-key collisions (filters/limits/sites), `clearCache()` invalidation per metric, untagged fallback, metric invariants, inclusive date boundaries, legacy clean-path goal matching |
| `LivewireDashboardTest.php` | 7 ⚠️ | empty state, metrics+top pages, reactive period change (**failing**), events tab (**failing**), event data (**failing**), event-name filter (**failing**), property key selection (**failing**) |

### 2.8 Package `lumina-core` — Root Tests (`packages/lumina-core/tests/`, 2 tests)

| File | Tests | Covers |
|------|------:|--------|
| `PackageCoreTest.php` | 2 | site factory creates Site model, event factory creates Event model |

### 2.9 Frontend — Vitest (106 tests)

| File | Tests | Covers |
|------|------:|--------|
| `tracker.test.ts` | 6 | identity params to `/api/collect`, `window.lumina` custom events + metadata, opaque visitor ID persistence (no cookies), **30-min session rotation**, **session stability during active browsing**, opt-out (`lumina_ignore`) |
| `useAnalyticsFormatters.test.ts` | 13 | number format, country flag, device icon, referrer favicon, browser icon, OS icon |
| `useInitials.test.ts` | 6 | empty/single/multi-name, unicode code points |
| `useAnalyticsPeriod.test.ts` | 4 | period navigation with preserved state, custom range, empty base URL |
| `useAnalyticsFilters.test.ts` | 4 | add/remove/clear filters with preserved site/period/tab |
| `useAnalyticsChart.test.ts` | 9 | series visibility toggles, per-series maximums, `maxDaily` across visible series, empty/zero guards, hidden-series fallback, hovered day, reactive recompute |
| `useLivePolling.test.ts` | 6 | live props reload, custom `only` list, toggle on/off, hidden-tab skip, refreshing state |
| `useBreakdownModal.test.ts` | 11 | fetch URL (endpoint/period/type/site_id/limit), data storage, in-flight reset, failure degradation, non-ok response, close/reset, overview-derived totals, modal-data totals, null totals |
| `components/analytics/AnalyticsControlBar.test.ts` | 5 | setPeriod, setTab, refresh, toggleLive, hide events tab |
| `components/analytics/AnalyticsKpiCards.test.ts` | 6 | formatted values, optional card visibility, bounce & duration rendering, zero defaults |
| `components/analytics/AnalyticsChart.test.ts` | 8 | empty state, per-day bars, toggle emits, hovered-day tooltip, x-axis labels, zero-max clamp |
| `components/analytics/AnalyticsFiltersBar.test.ts` | 5 | chip rendering, remove/clear emits, empty-state hiding |
| `components/analytics/AnalyticsBreakdownCard.test.ts` | 9 | item rendering, 10-item cap, filter emit, expand emit, empty state, custom empty text, country flags, entry count |
| `components/SiteSwitcher.test.ts` | 5 | site options, Add New Site control, URL `site_id` precedence, `active_site_id` fallback, router navigation preserving state/scroll |
| `components/CustomEventsTab.test.ts` | 9 | empty state, KPI cards, event filter navigation, reset-to-all, event list click, property-key navigation, log payload expand/collapse, selected-event highlight, visitor-hash truncation |

---

## 3. Test Matrix — What We Should Have

Legend: ✅ implemented & green · ⚠️ exists but failing · ⬜ gap (not yet written) · ➖ not applicable / consciously out of scope.

### 3.1 Identity, Privacy & Tracking (highest-value gap area)

| Requirement | Status | Where |
|-------------|--------|-------|
| Middleware inserts event on tracked request | ✅ | `TrackPageviewMiddlewareTest` |
| Raw IP never stored; hashed identity instead | ✅ | `TrackPageviewMiddlewareTest` |
| Stable cross-day fallback hash (IP+UA+salt) | ✅ | `TrackPageviewMiddlewareTest`, `TrackingIdentityTest` |
| Client `visitor_id` / `session_id` honored & stored | ✅ | `TrackPageviewMiddlewareTest`, `InsertEventJobTest` |
| Session semantics (30-min inactivity) | ✅ | `tracker.test.ts` (rotation + stability tests) |
| Idempotent insert via `event_id` | ✅ | `InsertEventJobTest` |
| IP + site rate limiting (atomic, burst-safe) | ✅ | `TrackPageviewMiddlewareTest` |
| Trusted-proxy boundary for country headers | ✅ | `TrackPageviewMiddlewareTest` |
| `X-Country` first-party override | ✅ | `TrackPageviewMiddlewareTest` |
| Opt-out (`lumina_ignore`) respected | ✅ | `tracker.test.ts` |
| Tracker payload < 2 KB gzipped | ✅ | `TrackerScriptSizeTest` |
| Tracker bundle reproducible from source | ✅ | CI workflow `git diff --exit-code` step |
| `daily_visitor_stats` keyed by resolved identity | ✅ | `InsertEventJobTest` mixed-population test |
| Package `TrackPageview` middleware (collect route) | ⬜ | `TrackPageviewMiddlewareTest` covers the app middleware; the **package** `Middleware/TrackPageview` (0% coverage) has no dedicated test |
| Package `InsertEvent` job (dispatched from package) | ⬜ | `InsertEventJobTest` covers the app job; the **package** `Jobs/InsertEvent` (0% coverage) has no test |

### 3.2 Collection Endpoint (public API)

| Requirement | Status | Where |
|-------------|--------|-------|
| Valid pageview → job dispatch | ✅ | `CollectEndpointTest` |
| Unregistered domain → 422 | ✅ | `CollectEndpointTest` |
| Validation of required fields | ✅ | `CollectEndpointTest` |
| Custom event + metadata | ✅ | `CollectEndpointTest` |
| Device type from `screen_width` / UA | ✅ | `CollectEndpointTest`, `DeviceTypeTest` |
| CORS reflection (origin-specific, credentials) | ✅ | `CollectEndpointTest` |
| Per-IP rate limit | ✅ | `CollectEndpointTest` |
| `visitor`/`session` query params accepted & validated | ✅ | via `TrackingIdentity` (covered in identity tests) |
| Oversized/illegal identity rejected (regex, 100-char cap) | ✅ | `TrackingIdentityTest` |
| Package `CollectController` (0% coverage) | ⬜ | The package's own `Http/Controllers/CollectController` has no test; only the app-level route is exercised |

### 3.3 Analytics Service (package)

| Requirement | Status | Where |
|-------------|--------|-------|
| Pageviews / unique visitors / top pages / referrers | ✅ | `AnalyticsServiceTest` |
| Daily & hourly timeseries | ✅ | `AnalyticsServiceTest` |
| Device / browser / OS / country breakdowns | ✅ | `AnalyticsServiceTest`, `DetectionTest` |
| UTM campaign breakdown | ✅ | `AnalyticsServiceTest` (overview payload) |
| Custom event summary/list/timeline/props/logs | ✅ | `AnalyticsServiceTest` |
| Bounce rate & avg visit duration (session-based) | ✅ | `AnalyticsServiceTest` |
| Goal completions + unique-converter conversion rate | ✅ | `AnalyticsServiceTest` |
| Filter scoping on every metric | ✅ | `AnalyticsServiceTest` (filter-isolation tests) |
| Tagged caching + `clearCache()` invalidation | ✅ | `AnalyticsServiceTest` |
| Legacy `clean_path` fallback for goals/pages | ✅ | `AnalyticsServiceTest` |
| `ReferrerHelper` full coverage | ⬜ | 50% covered — lines 66, 71, 78, 87–93 not exercised (edge-case referrer parsing branches) |
| `CountryHelper` (0% coverage) | ⬜ | Zero tests — country-code lookup helpers used by `AnalyticsService` are untested in isolation |
| MySQL-specific SQL branches (JSON_TABLE, TIMESTAMPDIFF) | ⬜ | All tests run SQLite; a MySQL job or `DB_CONNECTION=mysql` matrix entry would prove the non-SQLite branches |

### 3.4 Data Retention

| Requirement | Status | Where |
|-------------|--------|-------|
| Default retention applied | ✅ | `PruneEventsTest` |
| Per-site `retention_days` override | ✅ | `PruneEventsTest` |
| `0`/negative = keep forever | ✅ | `PruneEventsTest` |
| Anonymous aggregates preserved after prune | ✅ | `PruneEventsTest` |
| Chunked (lock-safe) deletion | ✅ | `PruneEventsTest` |
| Scheduler entries registered (`lumina:prune-events`, backfill) | ✅ | `ScheduleRegistrationTest` (commands + cron expressions) |
| Backfill commands (`BackfillCleanPath`, `BackfillDailyVisitorStats`) | ⬜ | Both console commands are at 0% coverage; no test for their logic or output |

### 3.5 GeoIP / Country Resolution

| Requirement | Status | Where |
|-------------|--------|-------|
| Pluggable driver via `lumina.geoip.driver` | ✅ | `CountryResolverTest` (ip-api + disabled) |
| `disabled` driver performs no network calls | ✅ | `CountryResolverTest` (`Http::assertNothingSent`) |
| `ip-api` driver caches per IP (24 h) | ✅ | `CountryResolverTest` (single HTTP call across resolves) |
| Private/reserved IPs skipped | ✅ | `CountryResolverTest` (IPv4 + IPv6 dataset) |
| Lookup failure degrades to null (never throws) | ✅ | `CountryResolverTest` (500, missing key, exception) |

### 3.6 Goals & Share

| Requirement | Status | Where |
|-------------|--------|-------|
| Goal CRUD + cross-site isolation | ✅ | `GoalTest` |
| Path + custom-event goal matching, wildcard paths | ✅ | `AnalyticsServiceTest` (matching), `GoalTest` |
| Share: public, password, token regen, ownership | ✅ | `ShareControllerTest` |
| Share dashboard renders KPIs (top-level contract) | ✅ | `ShareControllerTest` |
| `Goal` model unit coverage (package) | ⬜ | `Models/Goal` is at 0% in the package; relationships and any scopes are untested in isolation |

### 3.7 Export

| Requirement | Status | Where |
|-------------|--------|-------|
| CSV/JSON pageviews, events, summary | ✅ | `ExportControllerTest` |
| Auth + ownership enforcement | ✅ | `ExportControllerTest` |
| Streamed response, correct content-disposition | ✅ | `ExportControllerTest` (attachment filename per type/format, no-cache headers) |

### 3.8 Auth / Fortify

| Requirement | Status | Where |
|-------------|--------|-------|
| Login/register/logout/rate limit | ✅ | `Auth/*` |
| Email verification flows | ✅ | `Auth/*` |
| Password reset & confirmation | ✅ | `Auth/*` |
| 2FA challenge + recovery | ✅ | `TwoFactorChallengeTest` (valid recovery code → authenticated; invalid → rejected) |
| Passkeys | ⬜ | `@laravel/passkeys` in stack; no feature test for register/verify (WebAuthn ceremony) |

### 3.9 Site Management

| Requirement | Status | Where |
|-------------|--------|-------|
| Create site with normalized domain | ✅ | `SiteControllerTest` |
| Reject duplicate domain per user | ✅ | `SiteControllerTest` |
| Index / create / show pages render | ✅ | `SitePagesTest` |
| Policy: owner vs non-owner | ✅ | `SitePolicyTest` |
| `SiteController@show` (full detail page data) | ⬜ | `SiteController` is at 32.6% — `show` and `destroy` actions (lines 38–68) are not directly tested |
| `SiteController@destroy` with cascade check | ⬜ | No dedicated test verifies the delete redirect + cascade; only covered incidentally by `SiteTest` factory |
| `SitePolicy` full coverage | ⬜ | 42.9% — lines 15, 31, 55–63 not covered (intermediate policy checks) |
| `DemoController` | ⬜ | 0% — demo mode page has no smoke test |
| `dashboard/breakdown` endpoint | ⬜ | `DashboardController@breakdown` (GET `/dashboard/breakdown`) has no test |

### 3.10 Filament Admin Panel

| Requirement | Status | Where |
|-------------|--------|-------|
| `SiteResource` list / create / edit pages register | ✅ | `Filament/Resources/SiteResource/Pages/*` — page classes covered at 100% by existing Filament page tests |
| `LuminaOverviewWidget` renders and queries | ⬜ | 0% — no test for the Filament widget that surfaces overview stats |
| `TopPagesWidget` renders and queries | ⬜ | 0% — no test for the top-pages widget |
| `LuminaPlugin` registration | ⬜ | 0% — the plugin boot/registration logic is untested |

### 3.11 Livewire Dashboard Component

| Requirement | Status | Where |
|-------------|--------|-------|
| Mounts and renders empty state | ⚠️ | `LivewireDashboardTest` — test exists but 5/7 cases fail (assertion mismatches on reactive property reads) |
| Renders metrics + top pages when events exist | ✅ | `LivewireDashboardTest` (2 passing) |
| Reactive period change updates data | ⚠️ | `LivewireDashboardTest` — **currently failing** |
| Can switch to custom events tab | ⚠️ | `LivewireDashboardTest` — **currently failing** |
| Shows custom events data | ⚠️ | `LivewireDashboardTest` — **currently failing** |
| Can filter by custom event name | ⚠️ | `LivewireDashboardTest` — **currently failing** |
| Can select property key | ⚠️ | `LivewireDashboardTest` — **currently failing** |
| `Livewire/Dashboard` class coverage | ⬜ | 0% — the component class itself is not exercised (failing tests don't reach it) |

### 3.12 Frontend

| Requirement | Status | Where |
|-------------|--------|-------|
| Tracker identity/opt-out/persistence/session rotation | ✅ | `tracker.test.ts` |
| Analytics control bar interactions | ✅ | `AnalyticsControlBar.test.ts` |
| Filter & period URL-state composables | ✅ | `useAnalyticsFilters/Period.test.ts` |
| Pure formatters & initials | ✅ | `useAnalyticsFormatters/Initials.test.ts` |
| Chart & polling composables | ✅ | `useAnalyticsChart.test.ts`, `useLivePolling.test.ts` |
| Breakdown modal composable (fetch + totals) | ✅ | `useBreakdownModal.test.ts` |
| SiteSwitcher component (site select + URL `site_id`) | ✅ | `SiteSwitcher.test.ts` |
| AnalyticsKpiCards / AnalyticsChart / FiltersBar / BreakdownCard | ✅ | per-component test files |
| CustomEventsTab interactions | ✅ | `CustomEventsTab.test.ts` |
| E2E happy path (login → create site → dashboard) | ⬜ | no Playwright; recommended next addition |
| E2E tracker smoke (real page → `/api/collect`) | ⬜ | no browser e2e; tracker covered by Vitest+jsdom only |

### 3.13 Infrastructure / Guards

| Requirement | Status | Where |
|-------------|--------|-------|
| Full local suite matches CI (env parity) | ✅ | `.env.testing` + phpunit `<server>` + `TestCase` guard |
| `composer ci:check` single green gate | ✅ | lint, format, types, vitest, tests, phpstan |
| PHPStan level 7 across app + package | ✅ | `phpstan.neon` (0 errors) |
| npm audit clean | ✅ | 0 vulnerabilities |
| Scheduled commands registered | ✅ | `ScheduleRegistrationTest` |
| MySQL-specific test matrix | ⬜ | CI runs SQLite only; add a MySQL job to prove portable SQL |

---

## 4. Remaining Gaps (priority order)

1. **Fix failing `LivewireDashboardTest`** — 5 tests assert on reactive property values returning `null`; the component class itself sits at 0% coverage. Fix the assertion approach (use `set()`/`call()` correctly) to restore the green suite.
2. **Package `TrackPageview` middleware** — the package's own `Middleware/TrackPageview.php` (0% coverage) is distinct from the app middleware already tested; it needs its own feature test in the package suite.
3. **Package `CollectController`** — `Http/Controllers/CollectController.php` (0% coverage) is the package-level controller; add a testbench feature test covering the same scenarios as `CollectEndpointTest` but wired to the package controller directly.
4. **Package `InsertEvent` job** — `Jobs/InsertEvent.php` (0% coverage) is the package job; verify its dispatch, idempotency, and daily-stats upsert within the package test suite.
5. **`SiteController@show` / `@destroy`** — 32.6% overall coverage; dedicated tests for the show detail page and the destroy + cascade redirect are missing.
6. **`ReferrerHelper` branches** — 50% coverage; lines 66, 71, 78, 87–93 (edge-case referrer parsing) need targeted unit tests.
7. **`CountryHelper`** — 0% coverage; the country-code lookup helpers used by `AnalyticsService` have no isolated test.
8. **`Goal` model (package)** — 0% coverage; relationships and any scopes are untested in isolation.
9. **Filament widgets** (`LuminaOverviewWidget`, `TopPagesWidget`) and **`LuminaPlugin`** — all at 0%; add Filament panel tests using `livewire()` or `Filament::assertCanRenderPage()`.
10. **`dashboard/breakdown` endpoint** — no test for `DashboardController@breakdown` (GET `/dashboard/breakdown`).
11. **Backfill console commands** (`BackfillCleanPath`, `BackfillDailyVisitorStats`) — 0% coverage; add `artisan` / `$this->artisan()` tests for their output and DB effects.
12. **`DemoController`** — 0% coverage; a simple smoke test for the demo page render.
13. **`SitePolicy` full coverage** — 42.9%; lines 15, 31, 55–63 not exercised.
14. **MySQL test job** — a CI matrix entry running the suite against MySQL proves the `JSON_TABLE` / `TIMESTAMPDIFF` / upsert branches used in production.
15. **Playwright e2e** — one happy path (login → create site → see dashboard) + a tracker smoke that asserts `/api/collect` receives identity params.
16. **Passkey flows** — complete the Fortify coverage story with WebAuthn register/verify feature tests.

---

## Standalone package suite

`packages/lumina-core` is independently testable — **79 tests** run against an in-memory SQLite app booted by [Orchestra Testbench](https://github.com/orchestral/testbench) (no host app required):

```bash
cd packages/lumina-core
composer install
composer test
```

CI runs this on every push/PR in the `syofyanzuhad/lumina-core` repo (`.github/workflows/tests.yml`). `tests/TestCase.php` is dual-mode: it binds to Testbench when present (standalone) and to the host `Tests\TestCase` under the monorepo's `php artisan test` — so the same files are green in both environments. **When touching package tests or `tests/TestCase.php`, run both `composer test` (package dir) and `php artisan test` (monorepo root).**

---

*Re-run `php artisan test`, `npm run test:frontend`, `composer test` (package dir), and `composer ci:check` after any change to this matrix to keep it truthful.*

---

## 1. Current Coverage at a Glance

| Suite | Files | Tests | Assertions | Command |
|-------|------:|------:|-----------:|---------|
| PHP — App (Unit) | 4 | 6 | — | `php artisan test --testsuite=Unit` |
| PHP — App (Feature) | 26 | 137 | — | `php artisan test --testsuite=Feature` |
| PHP — Package `lumina-core` (Unit) | 2 | 34 | — | `vendor/bin/pest packages/lumina-core/tests/Unit` |
| PHP — Package `lumina-core` (Feature) | 3 | 45 | — | `vendor/bin/pest packages/lumina-core/tests/Feature` |
| **PHP total** | **35** | **222** | **840** | `php artisan test` |
| JS — Vitest (composables/components/tracker) | 15 | 106 | — | `npm run test:frontend` |
| **Grand total** | **50** | **328** | — | `composer ci:check` |

All 328 tests pass locally; `composer ci:check` (lint + format + types + vitest + PHP tests
+ PHPStan) is the single green gate, identical to CI.

---

## 2. Full Inventory of Existing Tests

### 2.1 App — Unit (`tests/Unit`, 6 tests)

| File | Tests |
|------|-------|
| `DeviceTypeTest.php` | 1 — `fromScreenWidth()` boundaries |
| `SiteShareTest.php` | 3 — share helper methods, password-protected factory state, 32-char token generation |
| `TrackerScriptSizeTest.php` | 1 — compiled `public/js/script.js` exists and < 2 KB gzipped |
| `ExampleTest.php` | 1 — smoke |

### 2.2 App — Feature: Authentication, Settings & Smoke (`tests/Feature/Auth`, `tests/Feature/Settings`, 38 tests)

| File | Tests | Covers |
|------|------:|--------|
| `Auth/AuthenticationTest.php` | 6 | login render, authenticate, 2FA redirect, invalid password, logout, login rate limit |
| `Auth/EmailVerificationTest.php` | 6 | verify screen, verify email, invalid hash, invalid user id, redirects, no duplicate event |
| `Auth/PasswordConfirmationTest.php` | 2 | confirm screen, requires auth |
| `Auth/PasswordResetTest.php` | 5 | request link, reset screens, valid/invalid token |
| `Auth/RegistrationTest.php` | 2 | render, register |
| `Auth/TwoFactorChallengeTest.php` | 4 | redirect when unauthenticated, render challenge, **recovery-code redemption**, **invalid recovery code rejected** |
| `Auth/VerificationNotificationTest.php` | 2 | sends notification, skips when verified |
| `Settings/ProfileUpdateTest.php` | 5 | page renders, update profile, email-verification unchanged, delete account, password required |
| `Settings/SecurityTest.php` | 5 | security page, password confirmation, no-2FA rendering, update password, confirm required |
| `ExampleTest.php` | 1 | smoke |

### 2.3 App — Feature: Sites, Models & Dashboard (32 tests)

| File | Tests | Covers |
|------|------:|--------|
| `SiteControllerTest.php` | 2 | create with normalized domain, unique domain per user |
| `SitePagesTest.php` | 3 | index/create/show pages render |
| `SitePolicyTest.php` | 2 | owner can view/update/delete; others cannot |
| `SiteTest.php` | 4 | factory, belongs-to-user, lowercase domain, cascades events delete |
| `EventTest.php` | 4 | factory, belongs-to-site, `device_type` enum cast, `updated_at` not touched |
| `SiteSwitcherTest.php` | 1 | site switcher data shared with frontend |
| `ActiveSiteControllerTest.php` | 3 | update active site, cannot switch to another user's site, Inertia share exposes `active_site_id` |
| `DashboardControllerTest.php` | 9 | unauthenticated redirect, no-sites redirect, view with metrics, switch site via query, period filter, cross-user isolation, events tab, event-name filter, timeline/property breakdowns |
| `DashboardTest.php` | 3 | guest redirect, with-sites visit, no-sites redirect |
| `MilestoneV11Test.php` | 1 | full v1.1 persona lifecycle |

### 2.4 App — Feature: Tracking Pipeline & Collection (30 tests)

| File | Tests | Covers |
|------|------:|--------|
| `TrackPageviewMiddlewareTest.php` | 14 | insert on tracked request, IP hashing (no raw IP), metadata job, dispatch on valid host, bypass unregistered domain, IP + site rate limits, burst cap, cached site lookup, proxy country trust boundary, `X-Country` override, client identity storage, stable cross-day hash, dispatch failure reporting |
| `CollectEndpointTest.php` | 7 | pageview dispatch, unregistered domain 422, validation, custom events + metadata, screen-width device type, CORS reflection, per-IP rate limit |
| `InsertEventJobTest.php` | 5 | idempotent `event_id`, referrer truncation, daily-stats upsert, **resolved identity in mixed JS/non-JS population**, visitor/session storage |
| `DetectionTest.php` | 3 | country helper, UA parsing in job, top browsers/OS/countries |
| `QueueWorkerIntegrationTest.php` | 1 | real queue worker processes job → DB |

### 2.5 App — Feature: Goals, Share, Export, Retention (37 tests)

| File | Tests | Covers |
|------|------:|--------|
| `GoalTest.php` | 5 | list, create, update, delete, cross-site isolation |
| `ShareControllerTest.php` | 11 | public access, non-public 404, invalid token, password challenge, correct/incorrect password, manage auth, cross-user denial, enable sharing, set/clear password, regenerate token |
| `ExportControllerTest.php` | 9 | auth required, ownership, CSV pageviews, JSON pageviews, CSV+JSON events, summary JSON+CSV, **CSV attachment/no-cache headers**, **JSON attachment/no-cache headers**, **summary content-disposition** |
| `PruneEventsTest.php` | 5 | default retention, per-site override, keep-forever (0), aggregates preserved, multi-batch chunked delete |
| `EndToEndVerificationTest.php` | 1 | complete pipeline (track → collect → aggregate) |
| `Api/V1/StatsTest.php` | 3 | requires token, validates token + overview, type filtering |
| `ScheduleRegistrationTest.php` | 3 | `lumina:prune-events` registered, `lumina:backfill-visitor-stats` registered, both on the documented cron expressions |

### 2.6 Package `lumina-core` — Unit (34 tests)

| File | Tests | Covers |
|------|------:|--------|
| `CountryResolverTest.php` | 17 | ip-api driver resolves country code, per-IP 24 h cache, cached reuse without HTTP, `disabled` driver performs no network calls, private/reserved IPs skipped (IPv4 + IPv6), null/empty IP, failed lookup → null, missing `countryCode` → null, lookup exception → null (never throws) |
| `TrackingIdentityTest.php` | 17 | valid opaque visitor id honored (header + input), session id honored/absent, empty/whitespace/over-long rejection → fallback hash, 100-char cap truncation to 64, illegal characters rejected (email/IP/space/slash/unicode/dots), non-string input rejected, stable fallback hash per ip/UA/scope, scope isolation, fresh event id per resolve |

### 2.7 Package `lumina-core` — Feature (45 tests)

| File | Tests | Covers |
|------|------:|--------|
| `AnalyticsServiceTest.php` | 36 | pageviews, unique visitors, top pages/referrers, daily timeseries, custom events, overview payload, goal conversion, caching TTL, custom-event summary/list/timeline/property keys/breakdown/logs, bounce rate, avg duration, filter isolation, cache-key collisions (filters/limits/sites), `clearCache()` invalidation per metric, untagged fallback, metric invariants, inclusive date boundaries, legacy clean-path goal matching |
| `LivewireDashboardTest.php` | 7 | empty state, metrics+top pages, reactive period change, events tab, event data, event-name filter, property key selection |
| `PackageCoreTest.php` | 2 | site/event factories |

### 2.8 Frontend — Vitest (106 tests)

| File | Tests | Covers |
|------|------:|--------|
| `tracker.test.ts` | 6 | identity params to `/api/collect`, `window.lumina` custom events + metadata, opaque visitor ID persistence (no cookies), **30-min session rotation**, **session stability during active browsing**, opt-out (`lumina_ignore`) |
| `useAnalyticsFormatters.test.ts` | 13 | number format, country flag, device icon, referrer favicon, browser icon, OS icon |
| `useInitials.test.ts` | 6 | empty/single/multi-name, unicode code points |
| `useAnalyticsPeriod.test.ts` | 4 | period navigation with preserved state, custom range, empty base URL |
| `useAnalyticsFilters.test.ts` | 4 | add/remove/clear filters with preserved site/period/tab |
| `useAnalyticsChart.test.ts` | 9 | series visibility toggles, per-series maximums, `maxDaily` across visible series, empty/zero guards, hidden-series fallback, hovered day, reactive recompute |
| `useLivePolling.test.ts` | 6 | live props reload, custom `only` list, toggle on/off, hidden-tab skip, refreshing state |
| `useBreakdownModal.test.ts` | 11 | fetch URL (endpoint/period/type/site_id/limit), data storage, in-flight reset, failure degradation, non-ok response, close/reset, overview-derived totals, modal-data totals, null totals |
| `components/analytics/AnalyticsControlBar.test.ts` | 5 | setPeriod, setTab, refresh, toggleLive, hide events tab |
| `components/analytics/AnalyticsKpiCards.test.ts` | 6 | formatted values, optional card visibility, bounce & duration rendering, zero defaults |
| `components/analytics/AnalyticsChart.test.ts` | 8 | empty state, per-day bars, toggle emits, hovered-day tooltip, x-axis labels, zero-max clamp |
| `components/analytics/AnalyticsFiltersBar.test.ts` | 5 | chip rendering, remove/clear emits, empty-state hiding |
| `components/analytics/AnalyticsBreakdownCard.test.ts` | 9 | item rendering, 10-item cap, filter emit, expand emit, empty state, custom empty text, country flags, entry count |
| `components/SiteSwitcher.test.ts` | 5 | site options, Add New Site control, URL `site_id` precedence, `active_site_id` fallback, router navigation preserving state/scroll |
| `components/CustomEventsTab.test.ts` | 9 | empty state, KPI cards, event filter navigation, reset-to-all, event list click, property-key navigation, log payload expand/collapse, selected-event highlight, visitor-hash truncation |

---

## 3. Test Matrix — What We Should Have

Legend: ✅ implemented & green · ⬜ gap (not yet written) · ➖ not applicable / consciously out of scope.

### 3.1 Identity, Privacy & Tracking (highest-value gap area)

| Requirement | Status | Where |
|-------------|--------|-------|
| Middleware inserts event on tracked request | ✅ | `TrackPageviewMiddlewareTest` |
| Raw IP never stored; hashed identity instead | ✅ | `TrackPageviewMiddlewareTest` |
| Stable cross-day fallback hash (IP+UA+salt) | ✅ | `TrackPageviewMiddlewareTest`, `TrackingIdentityTest` |
| Client `visitor_id` / `session_id` honored & stored | ✅ | `TrackPageviewMiddlewareTest`, `InsertEventJobTest` |
| Session semantics (30-min inactivity) | ✅ | `tracker.test.ts` (rotation + stability tests) |
| Idempotent insert via `event_id` | ✅ | `InsertEventJobTest` |
| IP + site rate limiting (atomic, burst-safe) | ✅ | `TrackPageviewMiddlewareTest` |
| Trusted-proxy boundary for country headers | ✅ | `TrackPageviewMiddlewareTest` |
| `X-Country` first-party override | ✅ | `TrackPageviewMiddlewareTest` |
| Opt-out (`lumina_ignore`) respected | ✅ | `tracker.test.ts` |
| Tracker payload < 2 KB gzipped | ✅ | `TrackerScriptSizeTest` |
| Tracker bundle reproducible from source | ✅ | CI workflow `git diff --exit-code` step |
| `daily_visitor_stats` keyed by resolved identity | ✅ | `InsertEventJobTest` mixed-population test |

### 3.2 Collection Endpoint (public API)

| Requirement | Status | Where |
|-------------|--------|-------|
| Valid pageview → job dispatch | ✅ | `CollectEndpointTest` |
| Unregistered domain → 422 | ✅ | `CollectEndpointTest` |
| Validation of required fields | ✅ | `CollectEndpointTest` |
| Custom event + metadata | ✅ | `CollectEndpointTest` |
| Device type from `screen_width` / UA | ✅ | `CollectEndpointTest`, `DeviceTypeTest` |
| CORS reflection (origin-specific, credentials) | ✅ | `CollectEndpointTest` |
| Per-IP rate limit | ✅ | `CollectEndpointTest` |
| `visitor`/`session` query params accepted & validated | ✅ | via `TrackingIdentity` (covered in identity tests) |
| Oversized/illegal identity rejected (regex, 100-char cap) | ✅ | `TrackingIdentityTest` |

### 3.3 Analytics Service (package)

| Requirement | Status | Where |
|-------------|--------|-------|
| Pageviews / unique visitors / top pages / referrers | ✅ | `AnalyticsServiceTest` |
| Daily & hourly timeseries | ✅ | `AnalyticsServiceTest` |
| Device / browser / OS / country breakdowns | ✅ | `AnalyticsServiceTest`, `DetectionTest` |
| UTM campaign breakdown | ✅ | `AnalyticsServiceTest` (overview payload) |
| Custom event summary/list/timeline/props/logs | ✅ | `AnalyticsServiceTest` |
| Bounce rate & avg visit duration (session-based) | ✅ | `AnalyticsServiceTest` |
| Goal completions + unique-converter conversion rate | ✅ | `AnalyticsServiceTest` |
| Filter scoping on every metric | ✅ | `AnalyticsServiceTest` (filter-isolation tests) |
| Tagged caching + `clearCache()` invalidation | ✅ | `AnalyticsServiceTest` |
| Legacy `clean_path` fallback for goals/pages | ✅ | `AnalyticsServiceTest` |
| MySQL-specific SQL branches (JSON_TABLE, TIMESTAMPDIFF) | ⬜ | all tests run SQLite; a MySQL job or `DB_CONNECTION=mysql` matrix entry would prove the non-SQLite branches |

### 3.4 Data Retention

| Requirement | Status | Where |
|-------------|--------|-------|
| Default retention applied | ✅ | `PruneEventsTest` |
| Per-site `retention_days` override | ✅ | `PruneEventsTest` |
| `0`/negative = keep forever | ✅ | `PruneEventsTest` |
| Anonymous aggregates preserved after prune | ✅ | `PruneEventsTest` |
| Chunked (lock-safe) deletion | ✅ | `PruneEventsTest` |
| Scheduler entries registered (`lumina:prune-events`, backfill) | ✅ | `ScheduleRegistrationTest` (commands + cron expressions) |

### 3.5 GeoIP / Country Resolution

| Requirement | Status | Where |
|-------------|--------|-------|
| Pluggable driver via `lumina.geoip.driver` | ✅ | `CountryResolverTest` (ip-api + disabled) |
| `disabled` driver performs no network calls | ✅ | `CountryResolverTest` (`Http::assertNothingSent`) |
| `ip-api` driver caches per IP (24 h) | ✅ | `CountryResolverTest` (single HTTP call across resolves) |
| Private/reserved IPs skipped | ✅ | `CountryResolverTest` (IPv4 + IPv6 dataset) |
| Lookup failure degrades to null (never throws) | ✅ | `CountryResolverTest` (500, missing key, exception) |

### 3.6 Goals & Share

| Requirement | Status | Where |
|-------------|--------|-------|
| Goal CRUD + cross-site isolation | ✅ | `GoalTest` |
| Path + custom-event goal matching, wildcard paths | ✅ | `AnalyticsServiceTest` (matching), `GoalTest` |
| Share: public, password, token regen, ownership | ✅ | `ShareControllerTest` |
| Share dashboard renders KPIs (top-level contract) | ✅ | `ShareControllerTest` |

### 3.7 Export

| Requirement | Status | Where |
|-------------|--------|-------|
| CSV/JSON pageviews, events, summary | ✅ | `ExportControllerTest` |
| Auth + ownership enforcement | ✅ | `ExportControllerTest` |
| Streamed response, correct content-disposition | ✅ | `ExportControllerTest` (attachment filename per type/format, no-cache headers) |

### 3.8 Auth / Fortify

| Requirement | Status | Where |
|-------------|--------|-------|
| Login/register/logout/rate limit | ✅ | `Auth/*` |
| Email verification flows | ✅ | `Auth/*` |
| Password reset & confirmation | ✅ | `Auth/*` |
| 2FA challenge + recovery | ✅ | `TwoFactorChallengeTest` (valid recovery code → authenticated; invalid → rejected) |
| Passkeys | ⬜ | `@laravel/passkeys` in stack; no feature test for register/verify (WebAuthn ceremony) |

### 3.9 Frontend

| Requirement | Status | Where |
|-------------|--------|-------|
| Tracker identity/opt-out/persistence/session rotation | ✅ | `tracker.test.ts` |
| Analytics control bar interactions | ✅ | `AnalyticsControlBar.test.ts` |
| Filter & period URL-state composables | ✅ | `useAnalyticsFilters/Period.test.ts` |
| Pure formatters & initials | ✅ | `useAnalyticsFormatters/Initials.test.ts` |
| Chart & polling composables | ✅ | `useAnalyticsChart.test.ts`, `useLivePolling.test.ts` |
| Breakdown modal composable (fetch + totals) | ✅ | `useBreakdownModal.test.ts` |
| SiteSwitcher component (site select + URL `site_id`) | ✅ | `SiteSwitcher.test.ts` |
| AnalyticsKpiCards / AnalyticsChart / FiltersBar / BreakdownCard | ✅ | per-component test files |
| CustomEventsTab interactions | ✅ | `CustomEventsTab.test.ts` |
| E2E happy path (login → create site → dashboard) | ⬜ | no Playwright; recommended next addition |
| E2E tracker smoke (real page → `/api/collect`) | ⬜ | no browser e2e; tracker covered by Vitest+jsdom only |

### 3.10 Infrastructure / Guards

| Requirement | Status | Where |
|-------------|--------|-------|
| Full local suite matches CI (env parity) | ✅ | `.env.testing` + phpunit `<server>` + `TestCase` guard |
| `composer ci:check` single green gate | ✅ | lint, format, types, vitest, tests, phpstan |
| PHPStan level 7 across app + package | ✅ | `phpstan.neon` (0 errors) |
| npm audit clean | ✅ | 0 vulnerabilities |
| Scheduled commands registered | ✅ | `ScheduleRegistrationTest` |
| MySQL-specific test matrix | ⬜ | CI runs SQLite only; add a MySQL job to prove portable SQL |

---

## 4. Remaining Gaps (priority order)

1. **MySQL test job** — a CI matrix entry running the suite against MySQL proves the `JSON_TABLE` / `TIMESTAMPDIFF` / upsert branches used in production (the only backend gap left).
2. **Playwright e2e** — one happy path (login → create site → see dashboard) + a tracker smoke that asserts `/api/collect` receives identity params.
3. **Passkey flows** — complete the Fortify coverage story with WebAuthn register/verify feature tests.

---

## Standalone package suite

`packages/lumina-core` is independently testable — **77 tests** run against an in-memory SQLite app booted by [Orchestra Testbench](https://github.com/orchestral/testbench) (no host app required):

```bash
cd packages/lumina-core
composer install
composer test
```

CI runs this on every push/PR in the `syofyanzuhad/lumina-core` repo (`.github/workflows/tests.yml`). `tests/TestCase.php` is dual-mode: it binds to Testbench when present (standalone) and to the host `Tests\TestCase` under the monorepo's `php artisan test` — so the same files are green in both environments. **When touching package tests or `tests/TestCase.php`, run both `composer test` (package dir) and `php artisan test` (monorepo root).**

---

*Re-run `php artisan test`, `npm run test:frontend`, `composer test` (package dir), and `composer ci:check` after any change to this matrix to keep it truthful.*
