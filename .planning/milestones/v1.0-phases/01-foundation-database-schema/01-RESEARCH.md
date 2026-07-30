# Research: Phase 1 — Foundation & Database Schema

## Executive Summary

Phase 1 establishes the fundamental data model for Lumina analytics: `sites` and `events` tables, Eloquent models, factories, seeders, PHP 8.3 backed enum (`DeviceType`), and Pest v4 feature test coverage. All migrations and queries must be cross-database compatible across **PostgreSQL** (production), **MySQL** (broader hosting), and **SQLite** (in-memory tests). Key constraint: no DB-specific raw SQL.

**Three critical decisions for the plan:**
1. Index only fixed-width columns (`site_id`, `visitor_hash`, `created_at`) — never long `VARCHAR` URLs on MySQL
2. Domain strings must be lowercased at write time — PostgreSQL is case-sensitive, MySQL is not
3. Events are immutable log entries — use `const UPDATED_AT = null` and `$table->timestamp('created_at')->useCurrent()` (no `updated_at` column)

## 1. Existing Codebase Patterns

- **Framework**: Laravel 13.17, PHP 8.3
- **Starter Kit**: Official Vue starter kit — Fortify, Inertia v3, Vue 3, Tailwind v4, TypeScript, Wayfinder
- **Testing**: Pest v4.7 with `pest-plugin-laravel` v4.1
  - `phpunit.xml`: SQLite in-memory for tests (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`)
  - `tests/Pest.php`: `pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature')` — `RefreshDatabase` is already global for Feature tests
- **Eloquent conventions**: Laravel 13 uses PHP 8 attributes (`#[Fillable]`, `#[Hidden]`) and the method-return pattern for casts: `protected function casts(): array`
- **Factory conventions**: Use `fake()` helper, extend `Factory<Model>`
- **Existing models**: `User` model uses `#[Fillable]`, `#[Hidden]` attributes and `casts()` method

## 2. Cross-DB Migration Compatibility

### `sites` Table Schema

| Column | Blueprint Method | Notes |
|--------|-----------------|-------|
| `id` | `$table->id()` | BigAutoIncrement, PK |
| `domain` | `$table->string('domain', 255)` | `unique()` — bounded, safe to index |
| `owner_id` | `$table->foreignId('owner_id')` | `constrained('users')->cascadeOnDelete()` |
| `created_at`, `updated_at` | `$table->timestamps()` | Standard Laravel timestamps |

### `events` Table Schema

| Column | Blueprint Method | Notes |
|--------|-----------------|-------|
| `id` | `$table->id()` | BigAutoIncrement, PK |
| `site_id` | `$table->foreignId('site_id')` | `constrained('sites')->cascadeOnDelete()` |
| `path` | `$table->string('path', 2048)` | NOT indexed — too long for MySQL |
| `referrer` | `$table->string('referrer', 2048)->nullable()` | NOT indexed — too long for MySQL |
| `visitor_hash` | `$table->string('visitor_hash', 64)` | SHA-256 hex = 64 chars, safe to index |
| `device_type` | `$table->string('device_type', 20)` | Enum stored as string |
| `country` | `$table->string('country', 2)->nullable()` | ISO-3166-1 alpha-2 |
| `created_at` | `$table->timestamp('created_at')->useCurrent()` | Immutable, no `updated_at` |

**Composite index**: `$table->index(['site_id', 'visitor_hash', 'created_at'])` — all fixed-width, MySQL-safe

### Key Gotchas

1. **Case sensitivity (domain)**: PostgreSQL is case-sensitive (`example.com ≠ EXAMPLE.COM`), MySQL is not. **Mitigation**: Always store domain lowercased (`Str::lower(trim($domain))`).
2. **MySQL index key length**: `VARCHAR(2048)` cannot be indexed without prefix (InnoDB limit). **Mitigation**: Never index `path` or `referrer`; only index bounded columns.
3. **`updated_at` on events**: Events are immutable logs — no `updated_at`. **Mitigation**: `const UPDATED_AT = null` on the model; omit `$table->timestamps()`, use `$table->timestamp('created_at')->useCurrent()` only.
4. **Foreign key enforcement on SQLite**: Laravel's SQLite config enables FK enforcement by default in test env — cascade deletes will work in tests.

## 3. Eloquent Model Conventions (Laravel 13)

### `App\Models\Site`

```php
#[Fillable(['domain', 'owner_id'])]
class Site extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['created_at' => 'datetime', 'updated_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'site_id');
    }
}
```

### `App\Models\Event`

```php
#[Fillable(['site_id', 'path', 'referrer', 'visitor_hash', 'device_type', 'country', 'created_at'])]
class Event extends Model
{
    use HasFactory;

    public const UPDATED_AT = null; // immutable log entry

    protected function casts(): array
    {
        return ['device_type' => DeviceType::class, 'created_at' => 'datetime'];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
```

## 4. Factory Patterns

### `SiteFactory`

```php
public function definition(): array
{
    return [
        'domain' => Str::lower(fake()->unique()->domainName()),
        'owner_id' => User::factory(),
    ];
}
```

### `EventFactory`

```php
public function definition(): array
{
    return [
        'site_id' => Site::factory(),
        'path' => '/' . fake()->slug(),
        'referrer' => fake()->optional(0.7)->url(),
        'visitor_hash' => hash('sha256', fake()->ipv4() . fake()->userAgent() . Str::random(16)),
        'device_type' => fake()->randomElement(DeviceType::cases()),
        'country' => fake()->optional(0.9)->countryCode(),
        'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
    ];
}

// States: desktop(), mobile(), tablet() — set device_type to respective enum case
```

## 5. Pest v4 Testing Patterns

`RefreshDatabase` is already global for `tests/Feature` (declared in `tests/Pest.php`). No need to add it per-file.

Idiomatic Pest v4:
```php
// tests/Feature/Models/SiteTest.php
it('can create a site for a user', function () {
    $site = Site::factory()->create();
    expect($site->owner)->toBeInstanceOf(User::class);
});

it('cascades deletion to events when a site is deleted', function () {
    $site = Site::factory()->has(Event::factory()->count(3))->create();
    $site->delete();
    expect(Event::count())->toBe(0);
});

// tests/Feature/Models/EventTest.php
it('casts device_type to DeviceType enum', function () {
    $event = Event::factory()->mobile()->create();
    expect($event->device_type)->toBe(DeviceType::Mobile);
});

it('has no updated_at column', function () {
    $event = Event::factory()->create();
    expect($event->updated_at)->toBeNull();
});
```

## 6. `visitor_hash` Implementation

- **Formula**: `hash('sha256', $ip . $userAgent . $dailySalt)`
- **Daily salt storage**: Laravel Cache key `lumina:salt:YYYY-MM-DD`, TTL = `now()->endOfDay()`
- **Fallback if cache flushed**: `hash('sha256', config('app.key') . now()->toDateString())` — deterministic, prevents hash drift within a day
- **Where computed**: Inside `InsertEvent` job (Phase 4) — not in the request cycle. Phase 1 only defines the schema; the hash logic is implemented in Phase 4.
- **Privacy**: SHA-256 is one-way. Daily salt prevents cross-day tracking and rainbow table attacks. No raw IP stored.

## 7. `device_type` Enum & Derivation

```php
// app/Enums/DeviceType.php
enum DeviceType: string
{
    case Desktop = 'desktop';
    case Mobile = 'mobile';
    case Tablet = 'tablet';
    case Unknown = 'unknown';

    public static function fromScreenWidth(?int $width): self
    {
        return match (true) {
            $width === null || $width <= 0 => self::Unknown,
            $width < 768 => self::Mobile,
            $width <= 1024 => self::Tablet,
            default => self::Desktop,
        };
    }
}
```

**Breakpoints**: Mobile < 768px | Tablet 768–1024px | Desktop > 1024px | Unknown = null/invalid

## 8. Country from IP

**MVP approach**: Read HTTP header set by CDN/cloud edge:
- `CF-IPCountry` (Cloudflare) — available on Laravel Cloud
- `CloudFront-Viewer-Country` (AWS CloudFront) — fallback
- Return `null` if no header present (graceful degradation)

**No IP geolocation library needed for MVP** — no `torann/geoip` dependency, no MaxMind database to maintain. Store as `nullable char(2)`, uppercase ISO-3166-1 alpha-2. Implemented in Phase 4 (ingest job), not Phase 1. Phase 1 only defines the column as `string(2)->nullable()`.

## Key Risks

1. **Domain case mismatch across DBs** — must lowercase at write time in `SiteFactory` and model; affects ingest lookup correctness
2. **MySQL index length limit** — do NOT index `path` or `referrer`; only the composite index on `(site_id, visitor_hash, created_at)`
3. **`updated_at` leaking into `events`** — events are append-only; `const UPDATED_AT = null` must be set or Eloquent will attempt to populate a non-existent column

## Recommended Approach

**Creation order** (respects dependencies):
1. `app/Enums/DeviceType.php` — used by Event model + factory
2. Migration: `create_sites_table`
3. Migration: `create_events_table` (depends on `sites`)
4. `app/Models/Site.php` — with `#[Fillable]`, `owner()`, `events()` relationships
5. `app/Models/Event.php` — with `#[Fillable]`, `const UPDATED_AT = null`, `DeviceType` cast, `site()` relationship
6. `database/factories/SiteFactory.php`
7. `database/factories/EventFactory.php` — with `desktop()`, `mobile()`, `tablet()` states
8. `database/seeders/SiteSeeder.php` and `EventSeeder.php`
9. `tests/Feature/Models/SiteTest.php`
10. `tests/Feature/Models/EventTest.php`

## Validation Architecture

- **Unit**: `php artisan test --compact --filter=SiteTest,EventTest` — all assertions pass on SQLite in-memory
- **Migration smoke**: `php artisan migrate:fresh` — no errors on configured DB
- **Pint**: `vendor/bin/pint --dirty --format agent` — no formatting violations
- **PHPStan**: `vendor/bin/phpstan analyse app/Models/Site.php app/Models/Event.php app/Enums/DeviceType.php` — no errors at configured level

---
*Research written: 2026-07-26*
