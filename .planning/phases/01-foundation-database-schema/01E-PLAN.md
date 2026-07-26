---
wave: 3
depends_on:
  - 01C-PLAN.md
  - 01D-PLAN.md
files_modified:
  - tests/Feature/SiteTest.php
  - tests/Feature/EventTest.php
  - tests/Unit/DeviceTypeTest.php
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
- Tests verify domain lowercase storage: create site with `ExAmPlE.cOm`, assert `sites` table has `example.com`
- Tests verify cascade delete: delete site, confirm events count is 0
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
- Test verifies `$event->device_type` is an instance of `App\Enums\DeviceType`
- Test verifies `$event->updated_at` is null (immutable log)
- Test verifies `$event->site` relationship returns correct `Site` instance
</acceptance_criteria>
</task>

<task id="01E-3">
<title>Write DeviceType unit tests</title>
<action>
Create `tests/Unit/DeviceTypeTest.php` using Pest syntax.
Test `DeviceType::fromScreenWidth()` across all boundary values:
- `fromScreenWidth(767)` returns `DeviceType::Mobile`
- `fromScreenWidth(768)` returns `DeviceType::Tablet`
- `fromScreenWidth(1024)` returns `DeviceType::Tablet`
- `fromScreenWidth(1025)` returns `DeviceType::Desktop`
- `fromScreenWidth(null)` returns `DeviceType::Unknown`
- `fromScreenWidth(0)` returns `DeviceType::Unknown`
- `fromScreenWidth(-1)` returns `DeviceType::Unknown`
Do NOT use `RefreshDatabase` (no DB access needed — pure logic test).
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/app/Enums/DeviceType.php (reason: verify exact enum case names and fromScreenWidth signature)
- /Users/macbookpro/Herd/lumina/tests/Pest.php (reason: understand global test configuration)
</read_first>
<acceptance_criteria>
- `tests/Unit/DeviceTypeTest.php` exists
- `php artisan test --compact --filter=DeviceTypeTest` exits 0
- All 7 boundary conditions above have explicit test assertions
</acceptance_criteria>
</task>

## must_haves

### truths
- `php artisan test --compact --filter=SiteTest` exits 0
- `php artisan test --compact --filter=EventTest` exits 0
- `php artisan test --compact --filter=DeviceTypeTest` exits 0
- All DeviceType::fromScreenWidth() boundary conditions (767/768/1024/1025/null/0/-1) have explicit test assertions
- Site cascade delete verified: deleting a site removes all its events
- Event device_type cast to DeviceType enum verified
- Event updated_at verified as null

### prohibitions
- statement: Tests explicitly import or declare `RefreshDatabase`
  status: resolved
  verification: Code review confirms `RefreshDatabase` is omitted from `tests/Feature/SiteTest.php` and `tests/Feature/EventTest.php`

## Artifacts this phase produces
- `tests/Feature/SiteTest.php` — Site model feature tests
- `tests/Feature/EventTest.php` — Event model feature tests
- `tests/Unit/DeviceTypeTest.php` — DeviceType enum unit tests
