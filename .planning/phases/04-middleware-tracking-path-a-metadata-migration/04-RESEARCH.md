# Phase 4 Research: Middleware Tracking (Path A) & Metadata Migration

## 1. Middleware Architecture in Packages

### How Package Middleware Works in Laravel 13

In Laravel 13's application-bootstrap style (`bootstrap/app.php`), middleware is registered via the `withMiddleware()` callback. There is NO `Kernel.php` — middleware aliases, groups, and global middleware are defined directly in `bootstrap/app.php` using the `Middleware` configurator object.

**Pattern observed in this project:**
```php
// bootstrap/app.php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        HandleAppearance::class,
        HandleInertiaRequests::class,
    ]);
})
```

### Package-Side Registration

`LuminaCoreServiceProvider::boot()` **cannot directly inject into `bootstrap/app.php`** — that file is evaluated at boot time before providers run. However, the service provider CAN:

1. **Register a named middleware alias** via `$this->app['router']->aliasMiddleware()`:
   ```php
   public function boot(): void
   {
       $this->app['router']->aliasMiddleware(
           'lumina.track',
           \Lumina\Core\Middleware\TrackPageview::class
       );
   }
   ```
   This registers the string alias `lumina.track` globally, which host apps use as: `Route::middleware('lumina.track')`.

2. **Alternatively** (for embedded/self-hosted mode), the host `bootstrap/app.php` adds the middleware directly to a group:
   ```php
   $middleware->web(append: [TrackPageview::class]);
   // OR per-route:
   Route::middleware(['auth', 'lumina.track'])->group(...);
   ```

### Recommended Approach for Phase 4

**Both** the alias registration AND opt-in pattern must be supported:
- `LuminaCoreServiceProvider` calls `$this->app['router']->aliasMiddleware('lumina.track', TrackPageview::class)` in `boot()`
- For the self-hosted app (`routes/web.php`), wrap page routes with `->middleware('lumina.track')`
- The middleware is **opt-in** — it only fires on routes it's explicitly applied to

### Middleware Anatomy (TrackPageview)

```php
namespace Lumina\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageview
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        // Fire-and-forget: dispatch tracking job AFTER response continues
        // (actually dispatch BEFORE returning, but job runs async via queue)
        return $response;
    }
}
```

**Important:** Dispatch the job *before* `return $response`, not after. Because the queue is async, the response still returns instantly. Do not use `terminate()` here — `terminate()` runs after the response is sent but it requires a terminating middleware interface, adds complexity, and has subtle gotchas with jobs that rely on the request context.

### Middleware File Location

```
packages/lumina-core/src/Middleware/TrackPageview.php
```

---

## 2. Visitor Hash Implementation

### Algorithm (PRIV-01 through PRIV-04)

```php
$ip        = $request->ip();
$userAgent = $request->userAgent() ?? '';
$dailySalt = Cache::remember(
    'lumina_daily_salt_' . now()->format('Y-m-d'),
    86400,
    fn () => \Illuminate\Support\Str::random(32)
);

$visitorHash = hash('sha256', $ip . $userAgent . $dailySalt);
```

**Why this satisfies requirements:**
- `PRIV-01`: Raw IP is never stored — only the hash goes into `events.visitor_hash`
- `PRIV-02`: Hash uses `IP + UserAgent + daily_salt`
- `PRIV-03`: Salt key includes the current date (`Y-m-d`), so it rotates at midnight automatically. The `86400` TTL is a max, but the key changes daily regardless.
- `PRIV-04`: SHA-256 is a one-way hash; given only the hash output, the IP cannot be recovered

### Cache Key Design

```
lumina_daily_salt_2026-07-30
lumina_daily_salt_2026-07-31  ← new key next day (old entry expires naturally)
```

**Testing consideration:** In tests (`CACHE_STORE=array`), `Cache::remember()` works fine. Tests must ensure salt is set in cache before testing visitor hash uniqueness.

### Pitfall: IP Extraction Behind Proxies

`$request->ip()` returns the REMOTE_ADDR by default. On Laravel Cloud / Cloudflare, the real client IP is in `X-Forwarded-For`. Laravel's `TrustProxies` middleware (already in the stack) handles this if configured. Since Laravel 11+, trusting all proxies is the default via `Request::setTrustedProxies(['*'], ...)` which is handled in `bootstrap/app.php`'s `trustProxies` helper. No extra action needed — `$request->ip()` already returns the correct client IP.

---

## 3. User-Agent Parsing for Device Type

### The Problem

The middleware path has NO JavaScript `screen.width` — device type must be inferred from the User-Agent string. The existing `DeviceType::fromScreenWidth()` method is useless here.

### Existing DeviceType Enum

```php
// packages/lumina-core/src/Enums/DeviceType.php
enum DeviceType: string {
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Desktop = 'desktop';
    case Unknown = 'unknown';

    public static function fromScreenWidth(?int $width): self { ... }
}
```

A new static method `DeviceType::fromUserAgent(string $ua): self` should be added to the enum.

### Option A: Simple Regex (Recommended — No New Dependency)

```php
public static function fromUserAgent(string $userAgent): self
{
    if (empty($userAgent)) {
        return self::Unknown;
    }

    $ua = strtolower($userAgent);

    // Tablet patterns (must check before mobile — some tablets include "mobile" in UA)
    if (preg_match('/ipad|tablet|kindle|playbook|silk|android(?!.*mobile)/i', $ua)) {
        return self::Tablet;
    }

    // Mobile patterns
    if (preg_match('/mobile|android|iphone|ipod|blackberry|opera mini|iemobile|wpdesktop/i', $ua)) {
        return self::Mobile;
    }

    return self::Desktop;
}
```

**Rationale:** This approach covers ~95%+ of real traffic correctly (the major UA patterns). UA parsing is fundamentally imprecise regardless of library. A 2KB regex is more maintainable and has zero dependency overhead for a library package.

### Option B: `jenssegers/agent` (~98% accuracy)

- Package: `jenssegers/agent` (wraps `mobiledetect/mobiledetectlib`)
- Requires adding to `packages/lumina-core/composer.json`
- Adds a dependency to the shared core package — every host app gets it
- **Decision: AVOID** — adds a mandatory dependency to `lumina/core` for marginal accuracy gain. Middleware tracking is secondary to JS tracking; UA parsing imprecision is acceptable here.

### Option C: `matomo/device-detector`

- More accurate but heavy (~600KB of UA YAML rules), slow first load
- Clearly overkill for v1 analytics
- **Decision: AVOID**

### Recommended: Option A (simple regex, no new dependency)

Add `DeviceType::fromUserAgent(string $ua): self` to the existing enum. No package additions required.

---

## 4. Country Derivation

### Primary: `X-Country` Header (Laravel Cloud Edge)

Laravel Cloud's edge CDN injects the visitor's country as an HTTP header. The header name is typically `X-Country` or `CF-IPCountry` (Cloudflare convention).

**Recommended approach — zero dependency:**
```php
$country = $request->header('X-Country')       // Laravel Cloud edge
        ?? $request->header('CF-IPCountry')    // Cloudflare
        ?? $request->header('X-Vercel-IP-Country') // Vercel
        ?? null;
```

Store as nullable 2-letter ISO code (`US`, `ID`, `GB`, etc.), or `null` if not available.

### Fallback: Skip GeoIP in v1

**Do NOT add a geoip package dependency** to `lumina/core` for v1. Reasons:
- `torann/geoip`, `stevebauman/location`, etc. require MaxMind database downloads or API keys
- Adds a mandatory dependency to the shared package
- Countries will be `null` for non-Cloud deployments in v1 — acceptable for MVP

**Decision:** Read `X-Country` header only; store null if absent. Document that deploying on Laravel Cloud with CDN edge will populate this field. The `country` column is already nullable in the migration.

---

## 5. InsertEvent Job

### File Location

```
packages/lumina-core/src/Jobs/InsertEvent.php
```

### Job Structure

```php
namespace Lumina\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;

class InsertEvent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $siteId,
        public readonly string $path,
        public readonly ?string $referrer,
        public readonly string $visitorHash,
        public readonly DeviceType $deviceType,
        public readonly ?string $country,
        public readonly ?array $metadata = null,
    ) {}

    public function handle(): void
    {
        Event::create([
            'site_id'      => $this->siteId,
            'path'         => $this->path,
            'referrer'     => $this->referrer,
            'visitor_hash' => $this->visitorHash,
            'device_type'  => $this->deviceType,
            'country'      => $this->country,
            'metadata'     => $this->metadata,
        ]);
    }
}
```

**Why pass primitives, not the Site model:**
- `SerializesModels` would store a model reference; if the site is deleted before the job runs, it throws `ModelNotFoundException`
- Passing `$siteId` as `int` is safer — the job can silently skip if site no longer exists, or throw a controlled exception

### ShouldQueue Implementation

`implements ShouldQueue` is required. In tests, `phpunit.xml` sets `QUEUE_CONNECTION=sync`, so jobs run synchronously without a worker — this is perfect for integration tests (dispatch → assert DB row). In production, `QUEUE_CONNECTION=database` (already the app default via `config/queue.php`).

### Dispatching from Middleware (No App Leakage)

```php
// In TrackPageview::handle()
InsertEvent::dispatch(
    siteId: $site->id,
    path: $request->path(),
    referrer: $request->header('Referer'),
    visitorHash: $visitorHash,
    deviceType: DeviceType::fromUserAgent($request->userAgent() ?? ''),
    country: $country,
    metadata: null, // middleware path = pageview = no metadata
);
```

The job is defined in `lumina/core` package, imported directly. No app-layer leakage.

### Site Lookup in Middleware

The middleware must resolve which `Site` this request belongs to, to get `$site->id`:

```php
// Option A: derive from request host (for embedded mode)
$host = $request->getHost(); // e.g. "example.com"
$site = Site::where('domain', $host)->first();
if (!$site) {
    return $next($request); // SITE-05: skip tracking for unregistered domains
}
```

This satisfies `SITE-05` — events are only tracked for registered domains.

---

## 6. Rate Limiting Per IP + Per Site

### Where to Define Rate Limiters

Rate limiters are defined in a service provider's `boot()`. Since this is package code, define them in `LuminaCoreServiceProvider::boot()`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    // ... existing migration registration ...

    $this->configureRateLimiting();
    $this->registerMiddlewareAlias();
}

private function configureRateLimiting(): void
{
    RateLimiter::for('lumina_ip', function (Request $request) {
        return Limit::perMinute(60)->by($request->ip());
    });

    RateLimiter::for('lumina_site', function (Request $request) {
        // site_id is not on the Request — must be resolved by middleware
        // Pattern: use request host as key for site-level limiting
        return Limit::perMinute(300)->by('site:'.$request->getHost());
    });
}
```

**Existing pattern in this project:** `FortifyServiceProvider::configureRateLimiting()` uses exactly the same `RateLimiter::for()` + `Limit::perMinute()->by()` pattern. Follow that exactly.

### Applying Rate Limiters in Middleware

```php
use Illuminate\Support\Facades\RateLimiter;

public function handle(Request $request, Closure $next): Response
{
    // Check IP rate limit — 60/min per IP
    if (RateLimiter::tooManyAttempts('lumina_ip:'.$request->ip(), 60)) {
        return $next($request); // Silent swallow — do NOT 429 the page request
    }
    RateLimiter::hit('lumina_ip:'.$request->ip(), 60);

    // Check site rate limit — 300/min per site domain
    $host = $request->getHost();
    if (RateLimiter::tooManyAttempts('lumina_site:'.$host, 300)) {
        return $next($request);
    }
    RateLimiter::hit('lumina_site:'.$host, 300);

    // ... proceed with tracking ...
    return $next($request);
}
```

**Critical design decision — silent swallow vs 429:**
- The middleware tracks pageviews on the HOST app's own pages (e.g., `/blog`, `/about`)
- Returning a `429` response would break the user's page load — unacceptable
- **Always return `$next($request)`** regardless of rate limit — just skip the tracking
- The API path (`POST /api/collect`) is different — it CAN and SHOULD return 429

**Note:** The named limiters defined in `LuminaCoreServiceProvider` with `RateLimiter::for()` use a callback that receives a `Request`. However, for middleware-based limiting (not route-based throttle), it's simpler to call `RateLimiter::tooManyAttempts()` and `RateLimiter::hit()` directly with manual keys, as shown above. The `RateLimiter::for()` named limiters are more appropriate for `throttle:lumina_ip` route middleware. Both approaches are valid.

**Recommended for middleware path:** Use `RateLimiter::tooManyAttempts()` + `RateLimiter::hit()` directly — no named limiter needed. Named limiters (`RateLimiter::for()`) are defined for Phase 5's API endpoint.

---

## 7. Metadata Migration

### New Migration File

A **new additive migration** file is added to the package (not modifying the original):

```
packages/lumina-core/database/migrations/2026_07_30_000001_add_metadata_to_events_table.php
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('country');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
```

### PostgreSQL + MySQL Compatibility

- `$table->json('metadata')` is supported by both PostgreSQL (`jsonb` is used internally) and MySQL 5.7.8+ (`JSON` type). Laravel's Blueprint abstracts the difference — this is compatible with `DATA-03`.
- `->nullable()` — essential: existing rows have no `metadata`; migration must not fail on non-empty tables
- `->after('country')` — MySQL-only hint for column ordering; on PostgreSQL it's ignored but harmless

### Event Model Update

After the migration, add `metadata` to the `Event` model:

```php
// In Event.php
protected function casts(): array
{
    return [
        'device_type' => DeviceType::class,
        'metadata'    => 'array',   // auto JSON encode/decode
    ];
}
```

---

## 8. Existing Patterns to Follow

### Current LuminaCoreServiceProvider (baseline)

```php
class LuminaCoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => database_path('migrations'),
            ], 'lumina-core-migrations');
        }
    }

    public function register(): void { }
}
```

**Phase 4 additions to `boot()`:**
1. `$this->app['router']->aliasMiddleware('lumina.track', TrackPageview::class)` — middleware alias registration
2. `$this->configureRateLimiting()` — named rate limiters (for Phase 5 API path)

**No changes needed to `register()`** — all setup is in `boot()`.

### Bootstrap/app.php Pattern

Middleware is registered via `withMiddleware(function (Middleware $middleware) {...})`. In `bootstrap/app.php`:
- Global middleware: `$middleware->append(SomeMiddleware::class)`
- Web group: `$middleware->web(append: [...])`
- API group: `$middleware->api(append: [...])`
- Aliases: `$middleware->alias(['name' => Class::class])`

For Phase 4, the `bootstrap/app.php` of the self-hosted app does NOT need to change — the middleware alias is registered by the service provider, and routes use it via `->middleware('lumina.track')`.

### Middleware File Signature (from HandleAppearance pattern)

```php
namespace Lumina\Core\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackPageview
{
    public function handle(Request $request, Closure $next): Response
    {
        // ...
        return $next($request);
    }
}
```

Exact same structure as `HandleAppearance.php` — the project's existing middleware pattern.

---

## 9. Pest Testing Patterns

### Test Suite Location

Phase 4 tests go in `tests/Feature/` (the root app's Feature suite), following the existing pattern (EventTest.php, SiteTest.php etc. are all there). NOT in `packages/lumina-core/tests/` — those are isolated unit tests. Integration tests that need the full app context (middleware stack, routing, DB) live in `tests/Feature/`.

### phpunit.xml — Queue Config in Tests

```xml
<env name="QUEUE_CONNECTION" value="sync"/>
```

Already set! With `sync` driver, `InsertEvent::dispatch(...)` runs **immediately** in the same request cycle. This means:
- Dispatch the job → job runs sync → assert DB row exists ✅
- No need for `Queue::fake()` when you want end-to-end verification

**Two test modes:**

**Mode A — End-to-end (sync queue):** Use default test setup (`QUEUE_CONNECTION=sync`). Assert the event row was actually inserted.

**Mode B — Job dispatch only (fake queue):** Use `Queue::fake()` to assert the job was dispatched with correct args, without inserting to DB. Useful for testing middleware behavior in isolation.

### Test Structure for Phase 4

```php
// tests/Feature/TrackPageviewMiddlewareTest.php

use Illuminate\Support\Facades\Queue;
use Lumina\Core\Jobs\InsertEvent;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

// Test 1: Middleware tracks a request → job dispatched
test('middleware dispatches InsertEvent job for registered domain', function () {
    Queue::fake();

    $site = Site::factory()->create(['domain' => 'example.com']);

    $this->get('/', [], ['HOST' => 'example.com']);

    Queue::assertDispatched(InsertEvent::class, function ($job) use ($site) {
        return $job->siteId === $site->id;
    });
});

// Test 2: Job inserted → event in DB (sync queue)
test('InsertEvent job inserts event row', function () {
    $site = Site::factory()->create(['domain' => 'example.com']);

    InsertEvent::dispatchSync(
        siteId: $site->id,
        path: '/test',
        referrer: null,
        visitorHash: hash('sha256', 'ip.useragent.salt'),
        deviceType: DeviceType::Desktop,
        country: 'US',
        metadata: null,
    );

    expect(Event::count())->toBe(1);
});

// Test 3: No raw IP stored
test('no raw IP address is stored in events table', function () {
    $site = Site::factory()->create(['domain' => 'example.com']);

    $this->get('/', [], ['HOST' => 'example.com']);

    $event = Event::first();
    expect($event->visitor_hash)
        ->not->toBe('127.0.0.1')    // not raw IP
        ->toHaveLength(64);          // SHA-256 hex = 64 chars
});

// Test 4: Unregistered domain skipped (SITE-05)
test('middleware skips tracking for unregistered domain', function () {
    Queue::fake();

    $this->get('/', [], ['HOST' => 'unregistered.com']);

    Queue::assertNotDispatched(InsertEvent::class);
});

// Test 5: Rate limit silently swallows (no 429)
test('rate-limited request still returns success response', function () {
    $site = Site::factory()->create(['domain' => 'example.com']);

    // Hit 60 times to exhaust IP rate limit
    for ($i = 0; $i < 61; $i++) {
        $response = $this->get('/', [], ['HOST' => 'example.com']);
    }

    // 61st request still gets 200, not 429
    $response->assertOk();
});
```

### Pest Function Style

All tests use `test()` function style with `expect()` chains — matching the existing `EventTest.php` and `SiteTest.php` patterns in this codebase (not PHPUnit class style).

---

## 10. Package Dependencies — Decision

### Current `packages/lumina-core/composer.json` requires:
- `php: ^8.3`
- `laravel/framework: ^13.0`

### Phase 4 Additions: NONE RECOMMENDED

| Candidate | Purpose | Decision | Reason |
|-----------|---------|----------|--------|
| `jenssegers/agent` | UA parsing | ❌ Skip | Adds mandatory dep to shared lib; simple regex is sufficient for v1 |
| `matomo/device-detector` | UA parsing | ❌ Skip | Heavy YAML rules; overkill |
| `torann/geoip` / `stevebauman/location` | IP→Country | ❌ Skip | Requires MaxMind DB or API key; CDN header is simpler |
| Any new dependency | — | ❌ Skip | Keep `lumina/core` lean; host apps have no extra baggage |

**Result:** `packages/lumina-core/composer.json` requires block stays as-is. All Phase 4 functionality uses PHP stdlib + Laravel framework (already required).

---

## Deliverable Checklist Summary

| Deliverable | Location | Status |
|-------------|----------|--------|
| `TrackPageview` middleware | `packages/lumina-core/src/Middleware/TrackPageview.php` | 🔲 |
| Middleware alias registration | `LuminaCoreServiceProvider::boot()` | 🔲 |
| `DeviceType::fromUserAgent()` method | `packages/lumina-core/src/Enums/DeviceType.php` | 🔲 |
| Daily salt + visitor hash logic | Inside `TrackPageview` | 🔲 |
| `InsertEvent` job | `packages/lumina-core/src/Jobs/InsertEvent.php` | 🔲 |
| `metadata` migration | `packages/lumina-core/database/migrations/..._add_metadata_to_events_table.php` | 🔲 |
| `metadata` cast on `Event` model | `packages/lumina-core/src/Models/Event.php` | 🔲 |
| Rate limiters in service provider | `LuminaCoreServiceProvider::boot()` | 🔲 |
| Middleware wired to routes | `routes/web.php` (opt-in) | 🔲 |
| Pest feature tests | `tests/Feature/TrackPageviewMiddlewareTest.php` | 🔲 |

---

## Validation Architecture

How to verify each deliverable for Nyquist validation:

### V1 — Middleware Dispatch (Unit: fake queue)
- **What:** `TrackPageview::handle()` dispatches `InsertEvent` with correct args
- **How:** `Queue::fake()` → `$this->get('/')` → `Queue::assertDispatched(InsertEvent::class)`
- **Proves:** INGEST-03, INGEST-04

### V2 — Job Inserts Row (Integration: sync queue)
- **What:** `InsertEvent::handle()` creates an `Event` row in DB
- **How:** `InsertEvent::dispatchSync(...)` → `expect(Event::count())->toBe(1)`
- **Proves:** INGEST-01, INGEST-03, QUEUE-01

### V3 — No Raw IP (Privacy: data assertion)
- **What:** `events.visitor_hash` is never a raw IP string
- **How:** After middleware fires → `Event::first()->visitor_hash` → assert is 64-char hex string, not IP format
- **Proves:** PRIV-01, PRIV-04

### V4 — Daily Salt Rotation (Unit: cache mock)
- **What:** Visitor hash changes day-to-day for same IP+UA
- **How:** Two `Cache::remember()` calls with different date keys → different salts → different hashes
- **Proves:** PRIV-02, PRIV-03

### V5 — Unregistered Domain Skipped (SITE-05)
- **What:** Middleware skips tracking if `domain` not in `sites` table
- **How:** `Queue::fake()` → request with unknown host → `Queue::assertNotDispatched(InsertEvent::class)`
- **Proves:** SITE-05

### V6 — Rate Limit (No 429 on Page)
- **What:** Rate-limited requests still return the page (silent swallow)
- **How:** Exhaust rate limit → assert response is 200, not 429
- **Proves:** INGEST-05

### V7 — Metadata Column Exists
- **What:** `events.metadata` column is nullable JSON
- **How:** `$this->assertDatabaseHas` after insert, or schema inspection in test
- **Proves:** DATA-02 (extended), metadata migration works cross-DB

### V8 — Device Type from UA
- **What:** `DeviceType::fromUserAgent()` returns correct enum case
- **How:** Unit test with known UA strings → assert Mobile/Tablet/Desktop/Unknown
- **Proves:** DATA-04 (middleware path variant)

---

## Key Decisions for Phase 4 Plan

1. **UA Parsing:** Simple regex in `DeviceType::fromUserAgent()` — no new dependency
2. **Country:** `X-Country` header only; null when absent — no geoip dependency
3. **Rate limit behavior:** Silent swallow (pass through) for middleware path; 429 for API path (Phase 5)
4. **Job args:** Primitives (not models) to avoid `ModelNotFoundException` on stale references
5. **Test queue mode:** Use `sync` queue by default for end-to-end tests; use `Queue::fake()` for dispatch-only tests
6. **Middleware registration:** Alias in `LuminaCoreServiceProvider::boot()`; applied opt-in per route group in `routes/web.php`
7. **No new package dependencies:** `lumina/core` stays lean

## RESEARCH COMPLETE
