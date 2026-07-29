# Phase 3: Package-Core Extraction — Research

## Key Findings

### 1. Composer Path Repository Setup
- **Path Repository Structure**: The shared core package will reside in `packages/lumina-core/` with its own `composer.json` declaring `"name": "lumina/core"` and `"type": "library"`.
- **Autoload Namespace**: PSR-4 mapping `"Lumina\\Core\\": "src/"` and `"Lumina\\Core\\Database\\Factories\\": "database/factories/"`.
- **Root `composer.json` Configuration**:
  Add `repositories` section to the root `composer.json`:
  ```json
  "repositories": [
      {
          "type": "path",
          "url": "packages/lumina-core",
          "options": {
              "symlink": true
          }
      }
  ]
  ```
  And require `"lumina/core": "*"` under `"require"`.
- **Resolution & Symlinks**: Composer resolves path repositories by creating a symlink at `vendor/lumina/core` pointing directly to `packages/lumina-core`. Composer automatically handles package discovery (`extra.laravel.providers`) during `post-autoload-dump`.

### 2. Model Namespace Migration
- **Models & Enums to Move**:
  - `App\Models\Site` → `Lumina\Core\Models\Site`
  - `App\Models\Event` → `Lumina\Core\Models\Event`
  - `App\Enums\DeviceType` → `Lumina\Core\Enums\DeviceType`
- **Table Name Conventions**: Eloquent derives table names via `Str::snake(Str::pluralStudly(class_basename($this)))`. Because `class_basename()` strips namespaces (`Lumina\Core\Models\Site` → `Site`), Eloquent automatically resolves the table names to `sites` and `events` without needing explicit `$table` overrides (though setting explicit `$table` properties is recommended for clarity).
- **User Relationship Decoupling**: In `Site::owner()`, instead of hardcoding `App\Models\User`, use `config('auth.providers.users.model', \App\Models\User::class)` so host applications can configure their own User model if needed.
- **Root Code Updates**: All occurrences of `App\Models\Site`, `App\Models\Event`, and `App\Enums\DeviceType` in controllers, policies, seeders, user model, and tests must be updated to `Lumina\Core\Models\*` and `Lumina\Core\Enums\*`.

### 3. Migration Publishing
- **Migration Relocation (D-02)**: Move `database/migrations/2026_07_26_111908_create_sites_table.php` and `2026_07_26_111909_create_events_table.php` into `packages/lumina-core/database/migrations/`. Delete original files from host `database/migrations/`.
- **Timestamp Preservation**: Migration filenames (`2026_07_26_111908_...`) must be preserved exactly to avoid re-running migrations on existing databases.
- **ServiceProvider Wireup**:
  In `LuminaCoreServiceProvider::boot()`:
  ```php
  if ($this->app->runningInConsole()) {
      $this->publishes([
          __DIR__.'/../database/migrations' => database_path('migrations'),
      ], 'lumina-core-migrations');
  }

  $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
  ```
  - `loadMigrationsFrom()` registers migrations automatically for host apps and testing.
  - `publishes()` allows host applications to publish package migrations to `database/migrations` via `php artisan vendor:publish --tag=lumina-core-migrations`.
  - Laravel's migrator de-duplicates migrations by filename (basename), preventing double execution even when published.

### 4. LuminaCoreServiceProvider Structure
- **Location**: `packages/lumina-core/src/LuminaCoreServiceProvider.php`
- **Responsibilities**:
  - `register()`: Minimal or empty for now.
  - `boot()`: Registers package migrations via `loadMigrationsFrom()`, defines migration publication via `publishes()`.
- **Package Discovery**: Configured in `packages/lumina-core/composer.json`:
  ```json
  "extra": {
      "laravel": {
          "providers": [
              "Lumina\\Core\\LuminaCoreServiceProvider"
          ]
      }
  }
  ```
  Host application can also register `Lumina\Core\LuminaCoreServiceProvider::class` in `bootstrap/providers.php`.

### 5. Pest Multi-Suite Setup
- **Directory**: `packages/lumina-core/tests/`
- **Pest & PHPUnit Configuration**:
  Update root `phpunit.xml` to include the package test suite:
  ```xml
  <testsuites>
      <testsuite name="Unit">
          <directory>tests/Unit</directory>
      </testsuite>
      <testsuite name="Feature">
          <directory>tests/Feature</directory>
      </testsuite>
      <testsuite name="PackageCore">
          <directory>packages/lumina-core/tests</directory>
      </testsuite>
  </testsuites>
  <source>
      <include>
          <directory>app</directory>
          <directory>packages/lumina-core/src</directory>
      </include>
  </source>
  ```
- **Pest Bootstrapping**: Create `packages/lumina-core/tests/Pest.php` extending `Tests\TestCase` with `RefreshDatabase`.
- **Execution**: `vendor/bin/pest` will automatically discover and run all suites, including `PackageCore`.

### 6. Factory Resolution After Namespace Move
- **Challenge**: Laravel's default factory resolver maps `App\Models\Site` to `Database\Factories\SiteFactory`. For package models like `Lumina\Core\Models\Site`, it looks for `Database\Factories\Lumina\Core\Models\SiteFactory`, which fails.
- **Solution**: Explicitly define `protected static function newFactory()` on `Site` and `Event` models pointing to package factories:
  ```php
  namespace Lumina\Core\Models;

  use Lumina\Core\Database\Factories\SiteFactory;

  class Site extends Model
  {
      use HasFactory;

      protected static function newFactory(): SiteFactory
      {
          return SiteFactory::new();
      }
  }
  ```
- **Factory Location**: `packages/lumina-core/database/factories/SiteFactory.php` and `EventFactory.php`, namespaced under `Lumina\Core\Database\Factories` with `$model` property set (`protected $model = Site::class;`).

### 7. Existing Code Inventory

#### Files Being Moved & Re-namespaced:
- `app/Models/Site.php` → `packages/lumina-core/src/Models/Site.php`
- `app/Models/Event.php` → `packages/lumina-core/src/Models/Event.php`
- `app/Enums/DeviceType.php` → `packages/lumina-core/src/Enums/DeviceType.php`
- `database/factories/SiteFactory.php` → `packages/lumina-core/database/factories/SiteFactory.php`
- `database/factories/EventFactory.php` → `packages/lumina-core/database/factories/EventFactory.php`
- `database/migrations/2026_07_26_111908_create_sites_table.php` → `packages/lumina-core/database/migrations/2026_07_26_111908_create_sites_table.php`
- `database/migrations/2026_07_26_111909_create_events_table.php` → `packages/lumina-core/database/migrations/2026_07_26_111909_create_events_table.php`

#### Files Being Created:
- `packages/lumina-core/composer.json`
- `packages/lumina-core/src/LuminaCoreServiceProvider.php`
- `packages/lumina-core/tests/Pest.php`
- `packages/lumina-core/tests/Feature/SiteTest.php`
- `packages/lumina-core/tests/Feature/EventTest.php`

#### Files Being Modified:
- `composer.json` (root: path repo entry + `"lumina/core": "*"`)
- `bootstrap/providers.php` (register `LuminaCoreServiceProvider::class`)
- `phpunit.xml` (add `PackageCore` test suite & package source path)
- `app/Models/User.php` (import `Lumina\Core\Models\Site`)
- `app/Http/Controllers/SiteController.php` (import `Lumina\Core\Models\Site`)
- `app/Policies/SitePolicy.php` (import `Lumina\Core\Models\Site`)
- `database/seeders/SiteSeeder.php` & `EventSeeder.php` (import package models/enums)
- `tests/Feature/` tests (`SiteTest.php`, `EventTest.php`, `SiteControllerTest.php`, `ActiveSiteControllerTest.php`, `SitePagesTest.php`, `SitePolicyTest.php`, `SiteSwitcherTest.php`)

### 8. Validation Architecture

#### Testable Behaviors (Nyquist Dimensions):
1. **Package Autoloading & Provider Boot**:
   - `Lumina\Core\Models\Site` and `Event` classes instantiate correctly.
   - `LuminaCoreServiceProvider` boots and registers package migrations without error.
2. **Factory Resolution**:
   - `Site::factory()->create()` generates valid database record using package factory.
   - `Event::factory()->create()` generates valid database record with `DeviceType` enum.
3. **Migration & Vendor Publishing**:
   - `php artisan migrate:fresh` runs migrations loaded from `packages/lumina-core/database/migrations/`.
   - `php artisan vendor:publish --tag=lumina-core-migrations` successfully copies migration files into `database/migrations/`.
4. **Pest Multi-Suite Pass**:
   - Both root `tests/Feature/` and package `packages/lumina-core/tests/` pass with zero failures.

## Risks & Gotchas
1. **Factory Resolution Failure**: If `newFactory()` is not explicitly declared on package models, calls to `Model::factory()` fail trying to locate `Database\Factories\Lumina\Core\Models\...`. Overriding `newFactory()` in models eliminates this risk.
2. **Migration Timestamp Drift**: Modifying migration timestamps breaks existing migration history. Keeping original timestamps (`2026_07_26_111908` and `2026_07_26_111909`) ensures smooth database compatibility.
3. **Autoload Cache Stale State**: Adding path repo requires running `composer dump-autoload` (or `composer update lumina/core`) to update autoload files and package discovery.

## Recommended Approach
1. Create `packages/lumina-core/` directory structure (`src/Models`, `src/Enums`, `database/migrations`, `database/factories`, `tests`).
2. Write `packages/lumina-core/composer.json` and `src/LuminaCoreServiceProvider.php`.
3. Update root `composer.json` with path repository and require `lumina/core:*`, then run `composer update lumina/core`.
4. Move `Site`, `Event`, `DeviceType`, migrations, and factories into package with updated namespaces and explicit `newFactory()` methods.
5. Update all imports across `app/`, `database/seeders/`, and `tests/`.
6. Add package test suite to `phpunit.xml` and create package model Pest tests.
7. Run `php artisan test` and verify `artisan vendor:publish --tag=lumina-core-migrations`.

## RESEARCH COMPLETE
