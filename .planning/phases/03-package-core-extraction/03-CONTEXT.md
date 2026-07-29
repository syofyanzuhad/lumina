# Phase 3: Package-Core Extraction - Context

**Gathered:** 2026-07-30
**Status:** Ready for planning

<domain>
## Phase Boundary

Extract shared logic into `packages/lumina-core` as a Composer path repository within the monorepo. This phase moves models (`Site`, `Event`), migrations, and establishes the `LuminaCoreServiceProvider` — the foundation that both embedded and standalone modes will consume. It does NOT build middleware, tracking script, or dashboard (those are Phases 4–8). The deliverable is a structurally sound package that can be `composer require`d into a fresh Laravel app and publish migrations via `artisan vendor:publish`.

</domain>

<decisions>
## Namespace Structure
- **D-01:** Use a flat `Lumina\Core\` namespace with subdirectories (Models, Middleware, Jobs, Services, etc.) — no nested module namespaces like `Lumina\Core\Analytics\` or `Lumina\Core\Tracking\`. Flat matches v1 scope and the project's "avoid premature abstraction" principle. Nesting can be introduced later if the package grows beyond analytics. — **Reversibility:** reversible — Can add nested namespaces later without breaking existing code if the flat namespace is well-organized.

## Migration Publishing Strategy
- **D-02:** Move existing `create_sites_table` and `create_events_table` migrations INTO the package (`packages/lumina-core/database/migrations/`). Delete the app-level migrations. The package becomes the single source of truth for schema. Host apps install via `artisan vendor:publish`. Timestamps must be preserved to avoid re-running on existing databases. — **Reversibility:** costly — Moving migrations back out after other apps depend on the package's published migrations is disruptive.

## Service Provider Behavior
- **D-03:** `LuminaCoreServiceProvider` registers migrations and publishes config only. Host apps must explicitly register middleware, routes, and other features. This is the Laravel-conventional approach for packages and gives host apps full control over what runs. No auto-registration of middleware or routes — the package provides the building blocks, the host app wires them. — **Reversibility:** reversible — Can add a convenience facade or auto-discovery later.

## Test Organization
- **D-04:** Package has its own `tests/` directory with Pest tests for models, relationships, and scopes. App-level tests remain as integration tests (HTTP, controllers, middleware). Both test suites run via `composer test` from root. Package tests travel with the code they test, ensuring the package is independently testable for Phase A gate verification. — **Reversibility:** reversible — Tests can be reorganized later.

## Claude's Discretion
None — all four areas were discussed and decided by the user.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Design Document
- `project-en.md` §2 — Locked decisions: package-core architecture, distribution model (path repo, subtree-split later), Laravel Cloud + VPS feature parity
- `project-en.md` §3 — Architecture diagram showing both paths converging on `InsertEvent` job and `events` table via `packages/lumina-core`

### Requirements
- `.planning/PROJECT.md` — Core value, technical constraints, and business context
- `.planning/REQUIREMENTS.md` § Data Model & Compatibility — DATA-01 through DATA-04 (events table schema, Postgres + MySQL compatibility)

### Architecture & Standards
- `.planning/codebase/CONVENTIONS.md` — Strict coding standards, ESLint, Pint, PHPStan
- `.planning/codebase/STACK.md` — Tailwind v4, Vue 3, Inertia.js, Reka UI, Lucide Vue
- `.planning/codebase/ARCHITECTURE.md` — Monolith architecture, Action classes, middleware-driven lifecycle

### Prior Phase Context
- `.planning/phases/02-site-management-crud/02-CONTEXT.md` — Domain validation, snippet delivery, site switching decisions
- `.planning/phases/05-tracking-script/03-CONTEXT.md` — Tracking script decisions (D-01 through D-06, now Phase 5)

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `app/Models/Site.php`: Has `domain` attribute with lowercase mutator, `owner()` and `events()` relationships, `#[Fillable]` attribute. Will move to `Lumina\Core\Models\Site`.
- `app/Models/Event.php`: Has `site()` relationship, `DeviceType` enum cast, `UPDATED_AT = null`. Will move to `Lumina\Core\Models\Event`.
- `app/Enums/DeviceType.php`: Enum used by Event model. Will move to `Lumina\Core\Enums\DeviceType`.
- `database/migrations/2026_07_26_111908_create_sites_table.php` and `2026_07_26_111909_create_events_table.php`: Will move to `packages/lumina-core/database/migrations/`.
- `database/factories/SiteFactory.php` and `EventFactory.php`: Will move to package factories.
- `app/Http/Requests/StoreSiteRequest.php`: Validation logic for site creation. Stays in app (host-app concern).
- `app/Http/Controllers/SiteController.php` and `ActiveSiteController.php`: Stay in app (host-app concern).
- `composer.json`: Currently has no `repositories` section. Needs path repository entry for `packages/lumina-core`.

### Established Patterns
- Monolith architecture using Laravel + Vue 3 + Inertia.js — the package-core is extracted FROM this monolith, not built separately.
- Domain stored clean (lowercase, stripped of protocol/www) — the mutator stays in the model, moves with it.
- Pest test framework with `tests/Pest.php` bootstrap — package tests will need their own Pest configuration.
- `#[Fillable]` PHP 8.3 attribute syntax used on models — must be preserved in the package.

### Integration Points
- Root `composer.json`: Must add `repositories` entry for path repo and `require` entry for `lumina/core`.
- `config/app.php` or `bootstrap/providers.php`: Must register `LuminaCoreServiceProvider`.
- Existing app tests (`tests/Feature/SiteTest.php`, `EventTest.php`, etc.): Must update namespace references from `App\Models\Site` to `Lumina\Core\Models\Site`.
- Phase 4 (Middleware Tracking): Will add `TrackPageview` middleware to the package's Middleware namespace.
- Phase 5 (Tracking Script & Ingest): Will add `CollectController` and routes — these stay in the app, not the package.
- Phase 7 (Aggregation): Will add `AnalyticsService` to the package's Services namespace.
- Phase 8 (Embedded Dashboard): Will add Livewire component to the package's Livewire namespace.

</code_context>

<specifics>
## Specific Ideas

- The package directory structure should follow Laravel package conventions: `src/` for PHP classes, `database/migrations/` for migrations, `database/factories/` for factories, `tests/` for Pest tests.
- The `composer.json` in `packages/lumina-core/` should set `"type": "library"` and use `"Lumina\\Core\\": "src/"` as the autoload namespace.
- Migration timestamps must be preserved exactly when moving — changing them would cause Laravel to treat them as new migrations on existing databases.
- The Phase A gate requires `composer require` into a throwaway test Laravel app to verify migrations publish correctly. This should be a manual verification step, not CI yet.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 03-Package-Core Extraction*
*Context gathered: 2026-07-30*