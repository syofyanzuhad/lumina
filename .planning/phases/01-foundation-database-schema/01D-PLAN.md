---
wave: 2
depends_on:
  - 01A-PLAN.md
  - 01B-PLAN.md
files_modified:
  - database/factories/SiteFactory.php
  - database/factories/EventFactory.php
autonomous: true
---

## Goal
Establish model factories and seeders for `Site` and `Event` to enable testing and local development seeding.

## Requirements
- DATA-01
- DATA-02

## Tasks

<task id="01D-1">
<title>Create SiteFactory</title>
<action>
Create file `database/factories/SiteFactory.php`.
Namespace `Database\Factories`.
Extend `Illuminate\Database\Eloquent\Factories\Factory`.
Set `$model = \App\Models\Site::class`.
In `definition()` return:
- `domain` => `$this->faker->unique()->domainName()`
- `owner_id` => `\App\Models\User::factory()`
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/database/factories/UserFactory.php (reason: factory patterns)
</read_first>
<acceptance_criteria>
- `database/factories/SiteFactory.php` exists and extends Factory
- Calling `Site::factory()->create()` generates a Site with a related User owner (when tests run)
</acceptance_criteria>
</task>

<task id="01D-2">
<title>Create EventFactory</title>
<action>
Create file `database/factories/EventFactory.php`.
Namespace `Database\Factories`.
Extend `Illuminate\Database\Eloquent\Factories\Factory`.
Set `$model = \App\Models\Event::class`.
In `definition()` return:
- `site_id` => `\App\Models\Site::factory()`
- `path` => `'/' . $this->faker->slug()`
- `referrer` => `$this->faker->url()`
- `visitor_hash` => `\Illuminate\Support\Str::random(32)`
- `device_type` => `$this->faker->randomElement(\App\Enums\DeviceType::cases())`
- `country` => `$this->faker->countryCode()`
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/database/factories/UserFactory.php (reason: factory patterns)
</read_first>
<acceptance_criteria>
- `database/factories/EventFactory.php` exists and extends Factory
- Calling `Event::factory()->create()` generates an Event with a related Site and valid enum device_type
</acceptance_criteria>
</task>

## must_haves

### truths
- Both factories can independently create records using `::factory()->create()`
- `EventFactory` uses the `DeviceType` enum for the `device_type` field

### prohibitions
- statement: Seeders or factories hardcode IDs or rely on existing database state
  status: resolved
  verification: Code review verifies `User::factory()` and `Site::factory()` are used for foreign keys in definitions

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
