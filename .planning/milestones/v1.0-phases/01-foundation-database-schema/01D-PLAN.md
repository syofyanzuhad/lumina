---
wave: 2
depends_on:
  - 01A-PLAN.md
  - 01B-PLAN.md
files_modified:
  - database/factories/SiteFactory.php
  - database/factories/EventFactory.php
  - database/seeders/SiteSeeder.php
  - database/seeders/EventSeeder.php
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
Set protected model type hint `Factory<Site>` in PHPDoc.
In `definition()` return:
- `domain` => `\Illuminate\Support\Str::lower($this->faker->unique()->domainName())` — must be lowercase (PostgreSQL is case-sensitive)
- `owner_id` => `\App\Models\User::factory()`
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/database/factories/UserFactory.php (reason: factory patterns and PHPDoc conventions)
- /Users/macbookpro/Herd/lumina/app/Models/Site.php (reason: model class to reference in factory)
</read_first>
<acceptance_criteria>
- `database/factories/SiteFactory.php` exists and extends Factory
- `definition()` produces `domain` as a lowercased string (call `Str::lower()` on faker domain)
- `Site::factory()->create()` generates a Site with a related User owner (when tests run)
- `vendor/bin/pint --dirty --format agent` exits 0 after file is written
</acceptance_criteria>
</task>

<task id="01D-2">
<title>Create EventFactory</title>
<action>
Create file `database/factories/EventFactory.php`.
Namespace `Database\Factories`.
Extend `Illuminate\Database\Eloquent\Factories\Factory`.
In `definition()` return:
- `site_id` => `\App\Models\Site::factory()`
- `path` => `'/' . $this->faker->slug()`
- `referrer` => `$this->faker->optional(0.7)->url()` — nullable 70% of the time
- `visitor_hash` => `hash('sha256', $this->faker->ipv4() . $this->faker->userAgent() . \Illuminate\Support\Str::random(16))` — produces 64-char SHA-256 hex
- `device_type` => `$this->faker->randomElement(\App\Enums\DeviceType::cases())`
- `country` => `$this->faker->optional(0.9)->countryCode()` — nullable 10% of the time
- `created_at` => `$this->faker->dateTimeBetween('-30 days', 'now')`

Also add named factory states as public methods: `desktop()`, `mobile()`, `tablet()` — each returns `$this->state(fn (array $attributes) => ['device_type' => DeviceType::Desktop|Mobile|Tablet])`.
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/database/factories/UserFactory.php (reason: factory patterns)
</read_first>
<acceptance_criteria>
- `database/factories/EventFactory.php` exists and extends Factory
- `visitor_hash` in definition is `hash('sha256', ...)` producing a 64-char hex string (not `Str::random(32)`)
- Factory states `desktop()`, `mobile()`, `tablet()` exist as public methods
- `Event::factory()->mobile()->create()` generates an Event with `device_type = DeviceType::Mobile`
- `vendor/bin/pint --dirty --format agent` exits 0 after factory files are written
</acceptance_criteria>
</task>

<task id="01D-3">
<title>Create SiteSeeder and EventSeeder</title>
<action>
Create `database/seeders/SiteSeeder.php` — uses `Site::factory()->count(3)->has(Event::factory()->count(10))->create()` to seed 3 sites with 10 events each.
Create `database/seeders/EventSeeder.php` — creates 50 events distributed across existing sites using `Event::factory()->count(50)->create()`; if no sites exist, creates one first.
Both seeders extend `Illuminate\Database\Seeder` and implement a `run(): void` method.
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/database/seeders/DatabaseSeeder.php (reason: existing seeder patterns and class conventions)
- /Users/macbookpro/Herd/lumina/database/factories/SiteFactory.php (reason: verify factory exists before referencing)
</read_first>
<acceptance_criteria>
- `database/seeders/SiteSeeder.php` exists with `run(): void` method
- `database/seeders/EventSeeder.php` exists with `run(): void` method
- `php artisan db:seed --class=SiteSeeder` exits 0
- `php artisan db:seed --class=EventSeeder` exits 0
- `vendor/bin/pint --dirty --format agent` exits 0 after seeder files are written
</acceptance_criteria>
</task>

## must_haves

### truths
- Both factories can independently create records using `::factory()->create()`
- `EventFactory` uses the `DeviceType` enum for the `device_type` field
- `EventFactory` produces `visitor_hash` as a 64-char SHA-256 hex string via `hash('sha256', ...)`
- `SiteFactory` produces `domain` as a lowercase string via `Str::lower()`
- `SiteSeeder` and `EventSeeder` exist and execute without error

### prohibitions
- statement: Seeders or factories hardcode IDs or rely on existing database state
  status: resolved
  verification: Code review verifies `User::factory()` and `Site::factory()` are used for foreign keys in definitions

## Artifacts this phase produces
- `database/factories/SiteFactory.php` — `SiteFactory`
- `database/factories/EventFactory.php` — `EventFactory` with `desktop()`, `mobile()`, `tablet()` states
- `database/seeders/SiteSeeder.php` — `SiteSeeder`
- `database/seeders/EventSeeder.php` — `EventSeeder`
