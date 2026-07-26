---
phase: 1
phase-slug: foundation-database-schema
date: 2026-07-26
---

# Validation Strategy: Phase 1 — Foundation & Database Schema

## Validation Architecture

### Dimension 1 — Happy Path
- `php artisan test --compact --filter=SiteTest` passes: site creation, owner relationship, cascade delete to events
- `php artisan test --compact --filter=EventTest` passes: event creation, device_type enum cast, site relationship, null updated_at
- `php artisan migrate:fresh` completes without error on SQLite

### Dimension 2 — Edge Cases
- `Event::factory()->create(['device_type' => DeviceType::Unknown])` — stores as string `'unknown'`, retrieves as `DeviceType::Unknown`
- `Event::factory()->create(['country' => null])` — nullable column accepts null
- `Event::factory()->create(['referrer' => null])` — nullable referrer accepted
- Domain `EXAMPLE.COM` stored as `example.com` — assert `Site::where('domain', 'example.com')->exists()` after factory create

### Dimension 3 — Boundary Conditions
- `DeviceType::fromScreenWidth(767)` → `DeviceType::Mobile`
- `DeviceType::fromScreenWidth(768)` → `DeviceType::Tablet`
- `DeviceType::fromScreenWidth(1024)` → `DeviceType::Tablet`
- `DeviceType::fromScreenWidth(1025)` → `DeviceType::Desktop`
- `DeviceType::fromScreenWidth(null)` → `DeviceType::Unknown`
- `DeviceType::fromScreenWidth(0)` → `DeviceType::Unknown`

### Dimension 4 — Failure Cases
- Creating an `Event` with non-existent `site_id` throws a DB integrity exception
- Creating a `Site` with non-existent `owner_id` throws a DB integrity exception
- Duplicate `domain` on `sites` table throws a unique constraint violation

### Dimension 5 — Performance Baseline
- `EventFactory::times(1000)->create()` completes in < 5s (factory generation benchmark)
- Composite index `(site_id, visitor_hash, created_at)` present: `php artisan schema:dump` or `SHOW INDEX FROM events` shows the index

### Dimension 6 — Integration
- `Site::factory()->has(Event::factory()->count(5))->create()` — site with 5 events, `$site->events->count()` returns 5
- `$site->delete()` cascade: all 5 events removed, `Event::where('site_id', $site->id)->count()` returns 0

### Dimension 7 — Regression
- `php artisan test --compact` full suite passes after all Phase 1 changes — no pre-existing tests broken

### Dimension 8 — Nyquist Coverage
- `visitor_hash` column is `string(64)` — SHA-256 hex output is exactly 64 chars
- `device_type` column stores the string value of the `DeviceType` enum (not integer)
- `Event::UPDATED_AT` is null — `$event->updated_at` returns null after factory create
- All migrations use only standard Blueprint methods (no `$table->json()`, no `$table->uuid()` with Postgres extension, no raw DB-specific SQL)

## Verification Commands

```bash
# Run phase tests
php artisan test --compact --filter=SiteTest
php artisan test --compact --filter=EventTest
php artisan test --compact --filter=DeviceTypeTest

# Fresh migration smoke test
php artisan migrate:fresh

# Code style
vendor/bin/pint --dirty --format agent

# Static analysis
vendor/bin/phpstan analyse app/Models/Site.php app/Models/Event.php app/Enums/DeviceType.php
```
