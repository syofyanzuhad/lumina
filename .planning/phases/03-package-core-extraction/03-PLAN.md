# Phase 3: Package-Core Extraction — Plan

**Phase:** 03
**Goal:** Extract shared logic into `packages/lumina-core` as a Composer path repository
**Granularity:** fine
**Requirements:** ARCH-01

<threat_model>
## Threat Model (ASVS L1)

### Threats Considered
- **Package autoloading integrity:** Path repo symlinks can expose host filesystem paths if not configured correctly. **Mitigation:** Use Composer's path repository with `symlink: true` which restricts resolution to the monorepo bounds and properly namespaces the code.
- **Migration file integrity:** Re-running migrations on an existing database will cause errors or data loss. **Mitigation:** Migration timestamps (`2026_07_26_111908` and `2026_07_26_111909`) must be preserved exactly when moving them to the package so Laravel tracks them correctly.
- **Unintended route/middleware exposure:** Auto-registering routes and middleware in the package can expose endpoints unintentionally. **Mitigation:** The `LuminaCoreServiceProvider` will NOT auto-register routes or middleware. It only provides bindings and publishes migrations, forcing the host app to explicitly opt-in.

### Not In Scope
- **Tracking Middleware/Script:** This phase only creates the structural package foundation. The tracking script and middleware (Phase 4 and 5) are out of scope.
</threat_model>

## Wave 1: Package Scaffold

### Task 1.1 — Create Package Directory Structure
**What:** Create the basic directory structure for the `lumina-core` package.
**Files:**
- `packages/lumina-core/src/`
- `packages/lumina-core/database/migrations/`
- `packages/lumina-core/database/factories/`
- `packages/lumina-core/tests/`
**Verification:** Run `ls packages/lumina-core` to verify all 4 directories exist.

### Task 1.2 — Create Package composer.json
**What:** Create `packages/lumina-core/composer.json` defining the package name, type, dependencies, and autoloading.
**Files:** `packages/lumina-core/composer.json`
**Content:**
```json
{
    "name": "lumina/core",
    "type": "library",
    "require": {
        "php": "^8.3",
        "laravel/framework": "^13.0"
    },
    "autoload": {
        "psr-4": {
            "Lumina\\Core\\": "src/",
            "Lumina\\Core\\Database\\Factories\\": "database/factories/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Lumina\\Core\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Lumina\\Core\\LuminaCoreServiceProvider"
            ]
        }
    }
}
```
**Verification:** Run `composer validate -d packages/lumina-core` to ensure the JSON is valid.

### Task 1.3 — Update Root composer.json
**What:** Register the path repository in the root `composer.json` and require the package.
**Files:** `composer.json`
**Steps:**
1. Add a `repositories` block at the root level (before `"require"`):
```json
    "repositories": [
        {
            "type": "path",
            "url": "packages/lumina-core",
            "options": {
                "symlink": true
            }
        }
    ],
```
2. Add `"lumina/core": "*"` to the `"require"` block.
**Verification:** Run `composer update lumina/core` in the root directory. It should succeed and symlink the package.

## Wave 2: Move Models & Enums

### Task 2.1 — Move and Update DeviceType Enum
**What:** Move `DeviceType.php` to the package and update its namespace.
**Files:** 
- `app/Enums/DeviceType.php` -> `packages/lumina-core/src/Enums/DeviceType.php`
**Steps:**
1. Move the file.
2. Change namespace from `namespace App\Enums;` to `namespace Lumina\Core\Enums;`.
**Verification:** File exists in the new location with the correct namespace.

### Task 2.2 — Move and Update Site Model
**What:** Move `Site.php` to the package, update namespace, decouple `owner()` from `App\Models\User`, add explicit `$table`, and override `newFactory()`.
**Files:**
- `app/Models/Site.php` -> `packages/lumina-core/src/Models/Site.php`
**Steps:**
1. Move the file.
2. Change namespace to `namespace Lumina\Core\Models;`.
3. Add `protected $table = 'sites';` property for explicitness inside package namespaces.
4. Update `owner()` to decouple from the host app's User model:
```php
    public function owner(): BelongsTo
    {
        return $this->belongsTo(
            config('auth.providers.users.model', \App\Models\User::class),
            'owner_id'
        );
    }
```
5. Add the `newFactory()` method:
```php
    protected static function newFactory()
    {
        return \Lumina\Core\Database\Factories\SiteFactory::new();
    }
```
**Verification:** `grep -q "auth.providers.users.model" packages/lumina-core/src/Models/Site.php`

### Task 2.3 — Move and Update Event Model
**What:** Move `Event.php` to the package, update namespace, fix imports, add explicit `$table`, and override `newFactory()`.
**Files:**
- `app/Models/Event.php` -> `packages/lumina-core/src/Models/Event.php`
**Steps:**
1. Move the file.
2. Change namespace to `namespace Lumina\Core\Models;`.
3. Update `use App\Enums\DeviceType;` to `use Lumina\Core\Enums\DeviceType;`.
4. Add `protected $table = 'events';` property for explicitness inside package namespaces.
5. Add the `newFactory()` method:
```php
    protected static function newFactory()
    {
        return \Lumina\Core\Database\Factories\EventFactory::new();
    }
```
**Verification:** `grep -q "Lumina\\\\Core\\\\Enums\\\\DeviceType" packages/lumina-core/src/Models/Event.php`

## Wave 3: Move Migrations & Factories

### Task 3.1 — Move Migrations
**What:** Move both migrations to the package, preserving exact filenames/timestamps.
**Files:**
- `database/migrations/2026_07_26_111908_create_sites_table.php` -> `packages/lumina-core/database/migrations/2026_07_26_111908_create_sites_table.php`
- `database/migrations/2026_07_26_111909_create_events_table.php` -> `packages/lumina-core/database/migrations/2026_07_26_111909_create_events_table.php`
**Steps:** Move the files directly. No internal code changes required.
**Verification:** Both files exist in `packages/lumina-core/database/migrations/` and not in `database/migrations/`.

### Task 3.2 — Move and Update SiteFactory
**What:** Move `SiteFactory.php` to the package and update namespace and model reference.
**Files:**
- `database/factories/SiteFactory.php` -> `packages/lumina-core/database/factories/SiteFactory.php`
**Steps:**
1. Move the file.
2. Change namespace to `namespace Lumina\Core\Database\Factories;`.
3. Update `use App\Models\Site;` to `use Lumina\Core\Models\Site;`.
4. Add `protected $model = Site::class;` inside the class.
**Verification:** `grep -q "namespace Lumina\\\\Core\\\\Database\\\\Factories" packages/lumina-core/database/factories/SiteFactory.php`

### Task 3.3 — Move and Update EventFactory
**What:** Move `EventFactory.php` to the package and update namespace and model reference.
**Files:**
- `database/factories/EventFactory.php` -> `packages/lumina-core/database/factories/EventFactory.php`
**Steps:**
1. Move the file.
2. Change namespace to `namespace Lumina\Core\Database\Factories;`.
3. Update `use App\Models\Event;` to `use Lumina\Core\Models\Event;`.
4. Update `use App\Enums\DeviceType;` to `use Lumina\Core\Enums\DeviceType;`.
5. Add `protected $model = Event::class;` inside the class.
**Verification:** `grep -q "use Lumina\\\\Core\\\\Enums\\\\DeviceType" packages/lumina-core/database/factories/EventFactory.php`

## Wave 4: ServiceProvider & Registration

### Task 4.1 — Create LuminaCoreServiceProvider
**What:** Create the package service provider to load and publish migrations.
**Files:** `packages/lumina-core/src/LuminaCoreServiceProvider.php`
**Content:**
```php
<?php

namespace Lumina\Core;

use Illuminate\Support\ServiceProvider;

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

    public function register(): void
    {
        //
    }
}
```
**Verification:** `php artisan tinker --execute "echo class_exists('Lumina\Core\LuminaCoreServiceProvider');"` returns 1.

### Task 4.2 — Register Provider in App
**What:** Add `LuminaCoreServiceProvider` to `bootstrap/providers.php`.
**Files:** `bootstrap/providers.php`
**Steps:** Add `Lumina\Core\LuminaCoreServiceProvider::class,` to the array.
**Verification:** File contains `Lumina\Core\LuminaCoreServiceProvider::class`.

## Wave 5: Update App References

### Task 5.1 — Update Controller References
**What:** Update all occurrences of `App\Models\Site` to `Lumina\Core\Models\Site`.
**Files:**
- `app/Http/Controllers/SiteController.php`
- `app/Http/Controllers/ActiveSiteController.php`
**Steps:** Change `use App\Models\Site;` to `use Lumina\Core\Models\Site;`.
**Verification:** Run `php -l` on both files to ensure valid syntax.

### Task 5.2 — Update User Model Relationship Import
**What:** `app/Models/User.php` has a `sites()` relationship using `Site::class`. Update it to reference the package model.
**Files:**
- `app/Models/User.php`
**Steps:** Add `use Lumina\Core\Models\Site;` to the imports (replacing any `use App\Models\Site;` if present).
**Verification:** `grep -q "Lumina\\\\Core\\\\Models\\\\Site" app/Models/User.php`

### Task 5.3 — Update Policy and Request References
**What:** Update model imports in requests and policies.
**Files:**
- `app/Policies/SitePolicy.php`
- `app/Http/Requests/StoreSiteRequest.php` (if Site is imported)
**Steps:** Change `use App\Models\Site;` to `use Lumina\Core\Models\Site;`.
**Verification:** Run `php -l` on modified files.

### Task 5.4 — Update Database Seeders
**What:** Update model imports in seeders.
**Files:**
- `database/seeders/SiteSeeder.php`
- `database/seeders/EventSeeder.php`
- `database/seeders/DatabaseSeeder.php` (if models are imported)
**Steps:** Change imports of `App\Models\Site` and `App\Models\Event` to their `Lumina\Core\Models\` equivalents.
**Verification:** `php artisan db:seed --class=DatabaseSeeder` runs without class not found errors (if possible to run).

## Wave 6: Test Suite Wiring & Verification

### Task 6.1 — Update Test Namespaces
**What:** Update all app test files to use the new `Lumina\Core` namespaces.
**Files:** All files in `tests/Feature/` (e.g., `SiteTest.php`, `EventTest.php`, `SiteControllerTest.php`, `SitePagesTest.php`, `SiteSwitcherTest.php`, `ActiveSiteControllerTest.php`, `SitePolicyTest.php`).
**Steps:**
1. Replace `use App\Models\Site;` with `use Lumina\Core\Models\Site;`.
2. Replace `use App\Models\Event;` with `use Lumina\Core\Models\Event;`.
3. Replace `use App\Enums\DeviceType;` with `use Lumina\Core\Enums\DeviceType;`.
**Verification:** `grep -r "App\\\\Models\\\\Site" tests/` returns no results.

### Task 6.2 — Configure Package Test Suite
**What:** Add a test suite for the package in `phpunit.xml`.
**Files:** `phpunit.xml`
**Steps:** Add this inside `<testsuites>`:
```xml
        <testsuite name="PackageCore">
            <directory>packages/lumina-core/tests</directory>
        </testsuite>
```
**Verification:** `php artisan test --compact` runs without complaining about an invalid XML structure.

### Task 6.3 — Create Package TestCase
**What:** Create Pest architecture and `TestCase` for package if needed.
**Files:** `packages/lumina-core/tests/TestCase.php`
**Content:**
```php
<?php

namespace Lumina\Core\Tests;

use Lumina\Core\LuminaCoreServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            LuminaCoreServiceProvider::class,
        ];
    }
}
```
*Note: Also run `composer require --dev orchestra/testbench` in root if needed for package testing, but standard Laravel app tests can just use the root app.*
**Verification:** File exists.

### Task 6.4 — Clean up and Format
**What:** Run code styling tools to ensure compliance.
**Files:** All modified PHP files
**Steps:** Run `vendor/bin/pint --dirty` in the root directory.
**Verification:** Command exits with 0.

## Acceptance Criteria

- [ ] `composer install` succeeds with path repo symlink
- [ ] `php artisan test --compact` passes all existing tests
- [ ] Package tests in `packages/lumina-core/tests/` run via `php artisan test --compact`
- [ ] `php artisan migrate:status` shows `create_sites_table` and `create_events_table` from package path
- [ ] `Site::factory()->create()` works without error
- [ ] `Event::factory()->create()` works without error
- [ ] `artisan vendor:publish --tag=lumina-core-migrations` copies migrations to host app's `database/migrations/`
- [ ] All controllers, requests, and tests reference `Lumina\Core\Models\Site` (not `App\Models\Site`)
- [ ] `vendor/bin/pint --dirty` exits 0 on all modified PHP files

## Nyquist Validation Coverage

| Dimension | Coverage | Notes |
|-----------|----------|-------|
| 1. Functional | High | Extracts models and migrations into a package structure, verified by existing tests. |
| 2. Edge Cases | Medium | Migration timestamps preserved explicitly to avoid duplicate migration errors on existing DBs. |
| 3. Error States | Low | N/A - Structural refactoring. Failures will primarily be class-not-found errors caught by tests. |
| 4. Integration | High | Ensures the package integrates seamlessly via `composer.json` path repository and ServiceProvider. |
| 5. Performance | Low | No performance impact. Class autoloading handles the new paths efficiently. |
| 6. Security | High | Symlinking isolates package. ServiceProvider requires explicit opt-in for routes/middleware. |
| 7. Reversibility | Medium | Migrations moving out is somewhat costly once published, but the namespace changes are easily reversible. |
| 8. Validation Strategy | High | Full reliance on PHPStan/Pint and Pest test suite execution to confirm correctness. |
