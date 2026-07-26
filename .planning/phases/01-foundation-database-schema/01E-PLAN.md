---
wave: 3
depends_on:
  - 01C-PLAN.md
  - 01D-PLAN.md
files_modified:
  - tests/Feature/SiteTest.php
  - tests/Feature/EventTest.php
autonomous: true
---

## Goal
Verify database migrations, Eloquent models, and model factories function correctly through automated Pest feature tests.

## Requirements
- DATA-01
- DATA-02
- DATA-04

## Tasks

<task id="01E-1">
<title>Write Site model tests</title>
<action>
Create file `tests/Feature/SiteTest.php`.
Use Pest syntax `test('...', function () { ... });` or `it('...', function () { ... });`.
Write the following tests:
1. It can create a site using the factory.
2. It belongs to a user (owner). Verify `$site->owner` is instance of `User`.
3. It converts the domain to lowercase on save. (Create site with `ExAmPlE.cOm`, assert database has `example.com`).

Note: Do not add `uses(RefreshDatabase::class);` as it is already global in `tests/Pest.php`.
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/tests/Pest.php (reason: verify RefreshDatabase global usage)
- /Users/macbookpro/Herd/lumina/phpunit.xml (reason: test DB config)
</read_first>
<acceptance_criteria>
- `php artisan test --compact --filter=SiteTest` exits 0
- Test suite verifies domain lowercase functionality
</acceptance_criteria>
</task>

<task id="01E-2">
<title>Write Event model tests</title>
<action>
Create file `tests/Feature/EventTest.php`.
Use Pest syntax `test('...', function () { ... });` or `it('...', function () { ... });`.
Write the following tests:
1. It can create an event using the factory.
2. It belongs to a site. Verify `$event->site` is instance of `Site`.
3. It casts device_type to the DeviceType enum. Verify `$event->device_type` is an instance of `App\Enums\DeviceType`.
4. It does not update the `updated_at` column. (Create event, save it again, check `updated_at` does not exist on model instance).
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/tests/Pest.php (reason: verify RefreshDatabase global usage)
- /Users/macbookpro/Herd/lumina/phpunit.xml (reason: test DB config)
</read_first>
<acceptance_criteria>
- `php artisan test --compact --filter=EventTest` exits 0
- Test suite verifies `device_type` casting
- Test suite verifies immutable nature of the `Event` model (no `updated_at`)
</acceptance_criteria>
</task>

## must_haves

### truths
- The test suite executes against SQLite in-memory without errors
- Pest tests successfully validate relationships, casts, and immutability

### prohibitions
- statement: Tests explicitly import or declare `RefreshDatabase`
  status: resolved
  verification: Code review confirms `RefreshDatabase` is omitted from `tests/Feature/SiteTest.php` and `tests/Feature/EventTest.php`

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
