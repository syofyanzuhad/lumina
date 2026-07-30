---
status: passed
phase: 03
verified: true
timestamp: 2026-07-30T00:47:39Z
---

# Phase 03 Verification: Package-Core Extraction

**Phase:** 03-package-core-extraction  
**Status:** PASSED  
**Requirements:** ARCH-01  
**Verified At:** 2026-07-30  

---

## Executive Summary

Phase 03 (Package-Core Extraction) has successfully extracted all shared domain logic — Eloquent models (`Site`, `Event`), Enums (`DeviceType`), database migrations, and factories — from the main application into `packages/lumina-core` configured as a Composer path repository.

All 61 automated unit and feature tests across both the root application and the new `PackageCore` test suite passed cleanly. All code formatting complies with project standards (`pint --test` passed).

---

## Requirements Traceability

| Requirement ID | Description | Source | Status | Evidence |
|----------------|-------------|--------|--------|----------|
| **ARCH-01** | Extract shared domain logic (`Site`, `Event`, `DeviceType`, migrations, factories) into `packages/lumina-core` as a Composer path repository | `ROADMAP.md` / `project-en.md` §2, §4 | **VERIFIED** | [packages/lumina-core/composer.json](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/composer.json), [LuminaCoreServiceProvider.php](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/src/LuminaCoreServiceProvider.php), [PackageCoreTest.php](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/tests/PackageCoreTest.php) |

> [!NOTE]
> `ARCH-01` encapsulates the architectural extraction of the schema domain models (previously defined under requirements `DATA-01` through `DATA-04` in Phase 1) into `packages/lumina-core` so that both Embedded (Phase A) and Standalone (Phase B) modes share a single source of truth.

---

## Must-Haves Verification Checklist

### 1. Package Scaffold & Autoloading
- [x] Directory structure exists at `packages/lumina-core/` with `src/`, `database/migrations/`, `database/factories/`, and `tests/`.
- [x] Package [`composer.json`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/composer.json) defines name `lumina/core`, type `library`, PSR-4 `Lumina\Core\` -> `src/`, and provider `Lumina\Core\LuminaCoreServiceProvider`.
- [x] Root [`composer.json`](file:///Users/macbookpro/Herd/lumina/composer.json) registers path repository `"url": "packages/lumina-core"` with `"symlink": true` and requires `"lumina/core": "@dev"`.
- [x] `composer validate -d packages/lumina-core` returns valid.

### 2. Domain Models & Enums Extraction
- [x] [`DeviceType.php`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/src/Enums/DeviceType.php) moved to package and namespaced to `Lumina\Core\Enums`.
- [x] [`Site.php`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/src/Models/Site.php) moved to package, namespaced to `Lumina\Core\Models`, explicit `$table = 'sites'`, decoupled `owner()` relationship using `config('auth.providers.users.model', \App\Models\User::class)`, and `newFactory()` override.
- [x] [`Event.php`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/src/Models/Event.php) moved to package, namespaced to `Lumina\Core\Models`, explicit `$table = 'events'`, cast to `DeviceType` enum, and `newFactory()` override.

### 3. Database Migrations & Factories Relocation
- [x] Migrations moved to [`packages/lumina-core/database/migrations/`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/database/migrations/) with exact timestamps (`2026_07_26_111908_create_sites_table.php` and `2026_07_26_111909_create_events_table.php`).
- [x] App-level migration directory cleaned of duplicates to prevent duplicate table creation.
- [x] [`SiteFactory.php`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/database/factories/SiteFactory.php) moved to package, namespaced to `Lumina\Core\Database\Factories`, model set to `Site::class`.
- [x] [`EventFactory.php`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/database/factories/EventFactory.php) moved to package, namespaced to `Lumina\Core\Database\Factories`, model set to `Event::class`.

### 4. ServiceProvider & Registration
- [x] [`LuminaCoreServiceProvider.php`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/src/LuminaCoreServiceProvider.php) boots migrations with `$this->loadMigrationsFrom(__DIR__.'/../database/migrations')` and registers publish tag `lumina-core-migrations`.
- [x] Provider registered in [`bootstrap/providers.php`](file:///Users/macbookpro/Herd/lumina/bootstrap/providers.php).
- [x] Provider verification confirmed via artisan command execution.

### 5. Application Codebase Refactoring
- [x] Controller references ([`SiteController.php`](file:///Users/macbookpro/Herd/lumina/app/Http/Controllers/SiteController.php), [`ActiveSiteController.php`](file:///Users/macbookpro/Herd/lumina/app/Http/Controllers/ActiveSiteController.php)) updated to `Lumina\Core\Models\Site`.
- [x] User model relationship in [`User.php`](file:///Users/macbookpro/Herd/lumina/app/Models/User.php) updated to `Lumina\Core\Models\Site`.
- [x] Policy references ([`SitePolicy.php`](file:///Users/macbookpro/Herd/lumina/app/Policies/SitePolicy.php)) updated to package models.
- [x] Database seeders ([`SiteSeeder.php`](file:///Users/macbookpro/Herd/lumina/database/seeders/SiteSeeder.php), [`EventSeeder.php`](file:///Users/macbookpro/Herd/lumina/database/seeders/EventSeeder.php)) updated to package models and enums.
- [x] All feature and unit test files updated to use `Lumina\Core\Models\*` and `Lumina\Core\Enums\*`.

### 6. Test Suite & Formatting Verification
- [x] Dedicated `PackageCore` test suite registered in [`phpunit.xml`](file:///Users/macbookpro/Herd/lumina/phpunit.xml).
- [x] [`TestCase.php`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/tests/TestCase.php) and [`PackageCoreTest.php`](file:///Users/macbookpro/Herd/lumina/packages/lumina-core/tests/PackageCoreTest.php) created and verifying factory instantiation.
- [x] `php artisan test --compact` executed and passed 61 out of 61 tests.
- [x] `vendor/bin/pint --test` executed and returned zero lint errors.

---

## Command Output Verification

### Package Validation
```bash
$ composer validate -d packages/lumina-core
./composer.json is valid
```

### Migration Status
```bash
$ php artisan migrate:status
 Migration name .................................................. Batch / Status 
 0001_01_01_000000_create_users_table .................................... [1] Ran 
 0001_01_01_000001_create_cache_table .................................... [1] Ran 
 0001_01_01_000002_create_jobs_table ..................................... [1] Ran 
 2024_01_01_000000_create_passkeys_table ................................. [1] Ran 
 2025_08_14_170933_add_two_factor_columns_to_users_table ................. [1] Ran 
 2026_07_26_111908_create_sites_table .................................... [1] Ran 
 2026_07_26_111909_create_events_table ................................... [1] Ran 
```

### Migration Publishing Tag Test
```bash
$ php artisan vendor:publish --tag=lumina-core-migrations
 INFO Publishing [lumina-core-migrations] assets. 
 Copying directory [packages/lumina-core/database/migrations] to [database/migrations] DONE
```

### Code Style (Pint)
```bash
$ vendor/bin/pint --test
{"tool":"pint","result":"passed"}
```

### Test Suite Execution
```bash
$ php artisan test --compact
{"tool":"pest","result":"passed","tests":61,"passed":61,"assertions":224,"duration_ms":2734}
```

---

## Human Verification Items

While automated test coverage is 100% passing within the repository, the following item is recommended for periodic manual verification during Phase A gate checks:

1. **External Host App Installation Test:**
   - Create a throwaway Laravel application outside the monorepo directory.
   - Add `"lumina/core": "*"` via path repository pointing to `/Users/macbookpro/Herd/lumina/packages/lumina-core`.
   - Run `php artisan vendor:publish --tag=lumina-core-migrations` and `php artisan migrate`.
   - Verify migrations publish and execute cleanly in a fresh external host database.

---

## Conclusion & Next Phase Readiness

Phase 03 is **fully verified and complete**. The core package foundation (`packages/lumina-core`) is in place, properly symlinked, autoloaded, and verified by the test suite.

The project is ready to proceed to **Phase 04 (Middleware Tracking & Metadata Migration)**.
