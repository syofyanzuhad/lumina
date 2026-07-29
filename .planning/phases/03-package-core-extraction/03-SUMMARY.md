---
phase: 03-package-core-extraction
plan: 03
subsystem: package-core
tags: [laravel, composer, package, monorepo, path-repository, elonquent]

requires:
  - phase: 02-site-management
    provides: Site & Event models, migrations, factories, and controller logic
provides:
  - Lumina Core Composer path package (lumina/core) with models, enums, migrations, factories, ServiceProvider, and PackageCore testsuite
affects: [04-middleware-tracking, 05-tracking-script, 06-queue-worker, 07-aggregation-queries, 08-embedded-dashboard]

tech-stack:
  added: [lumina/core (path repository package)]
  patterns: [Composer path repository, package service provider migration loading/publishing, decoupled User model resolution via config]

key-files:
  created:
    - packages/lumina-core/composer.json
    - packages/lumina-core/src/LuminaCoreServiceProvider.php
    - packages/lumina-core/src/Models/Site.php
    - packages/lumina-core/src/Models/Event.php
    - packages/lumina-core/src/Enums/DeviceType.php
    - packages/lumina-core/database/migrations/2026_07_26_111908_create_sites_table.php
    - packages/lumina-core/database/migrations/2026_07_26_111909_create_events_table.php
    - packages/lumina-core/database/factories/SiteFactory.php
    - packages/lumina-core/database/factories/EventFactory.php
    - packages/lumina-core/tests/TestCase.php
    - packages/lumina-core/tests/PackageCoreTest.php
  modified:
    - composer.json
    - bootstrap/providers.php
    - phpunit.xml
    - app/Models/User.php
    - app/Http/Controllers/SiteController.php
    - app/Policies/SitePolicy.php
    - database/seeders/SiteSeeder.php
    - database/seeders/EventSeeder.php

key-decisions:
  - "Extracted core models (Site, Event), enum (DeviceType), factories, and migrations into packages/lumina-core as a Composer path repository"
  - "Decoupled Site model owner relationship from host app using config('auth.providers.users.model', User::class)"
  - "Added PackageCore testsuite to phpunit.xml with dedicated TestCase and test verification suite"

patterns-established:
  - "Package isolation: Shared models and schema logic live inside packages/lumina-core/src under Lumina\\Core namespace"
  - "Explicit model table specification ($table) and newFactory override for package Eloquent models"

requirements-completed: [ARCH-01]

coverage:
  - id: D1
    description: "Extracted core logic (Site, Event, DeviceType, migrations, factories) into lumina/core path repository package"
    requirement: ARCH-01
    verification:
      - kind: unit
        ref: "packages/lumina-core/tests/PackageCoreTest.php#test_site_factory_creates_site_model"
        status: pass
      - kind: unit
        ref: "packages/lumina-core/tests/PackageCoreTest.php#test_event_factory_creates_event_model"
        status: pass
    human_judgment: false

duration: 15min
completed: 2026-07-30
status: complete
---

# Phase 03 Plan 03: Package-Core Extraction Summary

**Extracted Lumina domain models, migrations, factories, and enums into `packages/lumina-core` path repository package with `LuminaCoreServiceProvider` and dedicated testsuite.**

## Performance

- **Duration:** 15 min
- **Started:** 2026-07-30T00:42:36+07:00
- **Completed:** 2026-07-30T00:45:52+07:00
- **Tasks:** 17 tasks across 6 waves
- **Files modified:** 18

## Accomplishments
- Scaffolded `packages/lumina-core` package with `composer.json` defining `lumina/core` path repository and `Lumina\Core` PSR-4 autoloading.
- Relocated `DeviceType` enum, `Site` model, `Event` model, migrations, and factories into `packages/lumina-core`.
- Decoupled `Site` model `owner()` relationship to resolve host app's User model dynamically via `config('auth.providers.users.model')`.
- Created `LuminaCoreServiceProvider` to load and publish package migrations (`lumina-core-migrations`).
- Updated all app controllers, policies, seeders, and feature tests to import models from `Lumina\Core\Models` and `Lumina\Core\Enums`.
- Configured `PackageCore` testsuite in `phpunit.xml` with green passing test suite.

## Task Commits

Each task was committed atomically:

1. **Task 1.1: Create Package Directory Structure** - `3b35f7d` (feat)
2. **Task 1.2: Create Package composer.json** - `b985513` (feat)
3. **Task 1.3: Update Root composer.json** - `4554b3c` (feat)
4. **Task 2.1: Move and Update DeviceType Enum** - `b3e090c` (refactor)
5. **Task 2.2: Move and Update Site Model** - `e082fb3` (refactor)
6. **Task 2.3: Move and Update Event Model** - `59a7b9b` (refactor)
7. **Task 3.1: Move Migrations** - `bad8fd8` (refactor)
8. **Task 3.2: Move and Update SiteFactory** - `8434ca1` (refactor)
9. **Task 3.3: Move and Update EventFactory** - `f423c13` (refactor)
10. **Task 4.1 & 4.2: LuminaCoreServiceProvider & bootstrap/providers.php** - `0aa3770` (feat)
11. **Task 5.1: Update Controller References** - `21149c9` (refactor)
12. **Task 5.2: Update User Model Relationship Import** - `6afdfed` (refactor)
13. **Task 5.3: Update Policy References** - `51d8a8b` (refactor)
14. **Task 5.4: Update Database Seeders** - `a9c927d` (refactor)
15. **Task 6.1: Update Test Namespaces** - `6205d40` (refactor)
16. **Task 6.2: Configure Package Test Suite** - `86e5145` (test)
17. **Task 6.3: Create Package TestCase & PackageCoreTest** - `32a058f` (test)

## Files Created/Modified
- `packages/lumina-core/composer.json` - Core package metadata & PSR-4 autoload mapping
- `packages/lumina-core/src/LuminaCoreServiceProvider.php` - ServiceProvider for migration loading and publishing
- `packages/lumina-core/src/Models/Site.php` - Package Site Eloquent model
- `packages/lumina-core/src/Models/Event.php` - Package Event Eloquent model
- `packages/lumina-core/src/Enums/DeviceType.php` - Package DeviceType Enum
- `packages/lumina-core/database/migrations/2026_07_26_111908_create_sites_table.php` - Package Site table migration
- `packages/lumina-core/database/migrations/2026_07_26_111909_create_events_table.php` - Package Event table migration
- `packages/lumina-core/database/factories/SiteFactory.php` - Package Site factory
- `packages/lumina-core/database/factories/EventFactory.php` - Package Event factory
- `packages/lumina-core/tests/TestCase.php` & `PackageCoreTest.php` - Package testsuite
- `composer.json` - Added path repository `packages/lumina-core` and dependency `lumina/core: @dev`
- `bootstrap/providers.php` - Registered `LuminaCoreServiceProvider`
- `phpunit.xml` - Registered `PackageCore` testsuite

## Decisions Made
- Used `@dev` constraint for `lumina/core` in `composer.json` to allow path repository dev stability resolution.
- Decoupled `Site` owner relationship from hardcoded `App\Models\User` to `config('auth.providers.users.model')`.
- Maintained exact migration timestamps (`2026_07_26_111908` and `2026_07_26_111909`) to guarantee non-destructive migration status tracking in existing databases.

## Deviations from Plan
- **Composer Stability for Path Repository:** Updated root `composer.json` constraint to `@dev` so Composer path repository resolves stability properly.
- **Autoload-dev for Root Test Runner:** Added `"Lumina\\Core\\Tests\\": "packages/lumina-core/tests/"` to root `composer.json` `autoload-dev` to enable running package tests via artisan test runner.

## Issues Encountered
- None.

## User Setup Required
None - path repository symlinking is automatic via composer.

## Next Phase Readiness
- `packages/lumina-core` foundation complete and tested.
- Ready for Phase 4 (Middleware Tracking & Metadata Migration) or Phase 5 (Tracking Script & Ingest Endpoint).

---
*Phase: 03-package-core-extraction*
*Completed: 2026-07-30*
