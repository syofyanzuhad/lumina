---
wave: 1
depends_on: []
files_modified:
  - database/migrations/xxxx_xx_xx_xxxxxx_create_sites_table.php
  - database/migrations/xxxx_xx_xx_xxxxxx_create_events_table.php
autonomous: true
---

## Goal
Establish the database migrations for `sites` and `events` tables, ensuring cross-database compatibility (PostgreSQL and MySQL).

## Requirements
- DATA-01
- DATA-02
- DATA-03

## Tasks

<task id="01B-1">
<title>Create sites table migration</title>
<action>
Generate and modify migration file `database/migrations/xxxx_xx_xx_xxxxxx_create_sites_table.php` via `php artisan make:migration create_sites_table`.
In the `up` method, use `Schema::create('sites', ...)` with:
- `$table->id()`
- `$table->string('domain')->unique()`
- `$table->foreignId('owner_id')->constrained('users')->cascadeOnDelete()`
- `$table->timestamps()`

Note: Do not use `$table->string('domain')` without specifying length if you expect index length issues in MySQL, but Laravel 11/13 handles default string length gracefully.
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/database/migrations/ (reason: understand existing migration patterns)
</read_first>
<acceptance_criteria>
- A new file ending in `_create_sites_table.php` exists in `database/migrations/`
- Running `php artisan migrate:fresh` exits 0 on the configured test database
</acceptance_criteria>
</task>

<task id="01B-2">
<title>Create events table migration</title>
<action>
Generate and modify migration file `database/migrations/xxxx_xx_xx_xxxxxx_create_events_table.php` via `php artisan make:migration create_events_table`.
In the `up` method, use `Schema::create('events', ...)` with:
- `$table->id()`
- `$table->foreignId('site_id')->constrained('sites')->cascadeOnDelete()`
- `$table->string('path')`
- `$table->string('referrer')->nullable()`
- `$table->string('visitor_hash')`
- `$table->string('device_type')->nullable()`
- `$table->string('country')->nullable()`
- `$table->timestamp('created_at')->useCurrent()` (no `updated_at` column, DO NOT use `$table->timestamps()`)

Add a composite index:
- `$table->index(['site_id', 'visitor_hash', 'created_at'])`

Do NOT index `path` or `referrer`.
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/.planning/phases/01-foundation-database-schema/01-RESEARCH.md (reason: composite index rules and immutable table design)
</read_first>
<acceptance_criteria>
- A new file ending in `_create_events_table.php` exists in `database/migrations/`
- Running `php artisan migrate:fresh` exits 0 on the configured test database
- `events` table has no `updated_at` column
</acceptance_criteria>
</task>

## must_haves

### truths
- The `sites` table has `id`, `domain`, `owner_id`, `created_at`, and `updated_at`
- The `events` table is immutable (has `created_at` only) and has the required columns
- The `events` table has a composite index on `(site_id, visitor_hash, created_at)`

### prohibitions
- statement: Raw database-specific SQL is used in migrations
  status: resolved
  verification: Code review confirms only Blueprint methods are used
- statement: `path` or `referrer` are indexed in the `events` table
  status: resolved
  verification: Code review of the `events` migration confirms no index includes these columns

## Artifacts this phase produces
- `app/Enums/DeviceType.php` — `DeviceType` enum
- `database/migrations/*_create_sites_table.php` — `sites` migration
- `database/migrations/*_create_events_table.php` — `events` migration
- `app/Models/Site.php` — `Site` model
- `app/Models/Event.php` — `Event` model
- `database/factories/SiteFactory.php` — `Site` factory
- `database/factories/EventFactory.php` — `Event` factory
- `database/seeders/SiteSeeder.php` — `Site` seeder
- `database/seeders/EventSeeder.php` — `Event` seeder
- `tests/Feature/SiteTest.php` — `Site` Pest tests
- `tests/Feature/EventTest.php` — `Event` Pest tests
