---
wave: 2
depends_on:
  - 01A-PLAN.md
  - 01B-PLAN.md
files_modified:
  - app/Models/Site.php
  - app/Models/Event.php
autonomous: true
---

## Goal
Implement the `Site` and `Event` Eloquent models with proper relationships, fillable attributes, and type casts.

## Requirements
- DATA-01
- DATA-02
- DATA-04

## Tasks

<task id="01C-1">
<title>Create Site model</title>
<action>
Create file `app/Models/Site.php`.
Class `Site` extends `Illuminate\Database\Eloquent\Model`.
Add `#[Fillable(['domain', 'owner_id'])]` attribute above the class (import `Illuminate\Database\Eloquent\Attributes\Fillable`).
Add a `owner()` method returning `BelongsTo` relation to `User::class`.
Add an `events()` method returning `HasMany` relation to `Event::class`.

Override the `booted` method or use a model event to ensure `domain` is converted to lowercase before saving. Or use a mutator:
```php
protected function domain(): \Illuminate\Database\Eloquent\Casts\Attribute
{
    return \Illuminate\Database\Eloquent\Casts\Attribute::make(
        set: fn (string $value) => \Illuminate\Support\Str::lower($value),
    );
}
```
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/app/Models/User.php (reason: understand existing model conventions)
- /Users/macbookpro/Herd/lumina/.planning/phases/01-foundation-database-schema/01-RESEARCH.md (reason: domain lowercase requirement)
</read_first>
<acceptance_criteria>
- `app/Models/Site.php` exists and uses PHP 8 `#[Fillable]` attribute (not `$fillable` array)
- `Site` model has `owner(): BelongsTo` and `events(): HasMany` relationship methods
- `Site` model has a `domain` mutator (Attribute cast or booted observer) that lowercases values on set
- `vendor/bin/pint --dirty --format agent` exits 0 after `app/Models/Site.php` is written
</acceptance_criteria>
</task>

<task id="01C-2">
<title>Create Event model</title>
<action>
Create file `app/Models/Event.php`.
Class `Event` extends `Illuminate\Database\Eloquent\Model`.
Add `#[Fillable(['site_id', 'path', 'referrer', 'visitor_hash', 'device_type', 'country'])]` attribute above the class (import `Illuminate\Database\Eloquent\Attributes\Fillable`).

Set `const UPDATED_AT = null;` to disable the `updated_at` timestamp.

Add a `casts` method:
```php
protected function casts(): array
{
    return [
        'device_type' => \App\Enums\DeviceType::class,
    ];
}
```

Add a `site()` method returning `BelongsTo` relation to `Site::class`.
</action>
<read_first>
- /Users/macbookpro/Herd/lumina/.planning/phases/01-foundation-database-schema/01-RESEARCH.md (reason: immutable model requirements)
</read_first>
<acceptance_criteria>
- `app/Models/Event.php` exists and uses PHP 8 `#[Fillable]` attribute (not `$fillable` array)
- `Event::UPDATED_AT` constant is null
- The `casts()` method returns `['device_type' => \App\Enums\DeviceType::class]`
- `Event` model has `site(): BelongsTo` relationship method
- `vendor/bin/pint --dirty --format agent` exits 0 after `app/Models/Event.php` is written
</acceptance_criteria>
</task>

## must_haves

### truths
- The `Event` model does not try to update `updated_at` on save
- Both models use Laravel 13 `#[Fillable]` attributes, not `$fillable` arrays
- The `Site` model correctly converts domain names to lowercase on save

### prohibitions
- statement: `Event` model has `$fillable` array property
  status: resolved
  verification: Code review confirms `#[Fillable]` attribute is used instead of `$fillable` array property

## Artifacts this phase produces
- `app/Models/Site.php` — `Site` Eloquent model with `owner()`, `events()` relationships and domain lowercase mutator
- `app/Models/Event.php` — `Event` Eloquent model with `const UPDATED_AT = null`, `DeviceType` cast, `site()` relationship
