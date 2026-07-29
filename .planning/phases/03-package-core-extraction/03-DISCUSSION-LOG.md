# Phase 3: Package-Core Extraction - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-07-30
**Phase:** 03-package-core-extraction
**Areas discussed:** Namespace structure, Migration publishing strategy, Service provider behavior, Test organization

---

## Namespace Structure

| Option | Description | Selected |
|--------|-------------|----------|
| Flat namespace | Single `Lumina\Core\` with subdirectories (Models, Middleware, Jobs, etc.) | ✓ |
| Nested namespaces | `Lumina\Core\Analytics\`, `Lumina\Core\Tracking\`, etc. | |
| Flat + interfaces | Flat namespace plus `Concerns`/`Contracts` for interfaces | |

**User's choice:** Flat namespace
**Notes:** Consistent with project-en.md "avoid premature abstraction" principle. Nesting can be added later if the package grows.

---

## Migration Publishing Strategy

| Option | Description | Selected |
|--------|-------------|----------|
| Move into package | Move existing migrations to `packages/lumina-core/database/migrations/`, delete app-level copies | ✓ |
| Keep in app | App keeps migrations, package has none | |
| Package provides, app symlinks | Package has migrations, monorepo app references via symlink | |

**User's choice:** Move into package
**Notes:** Single source of truth. Timestamps must be preserved exactly to avoid re-running on existing databases.

---

## Service Provider Behavior

| Option | Description | Selected |
|--------|-------------|----------|
| Explicit opt-in | ServiceProvider registers migrations + publishes config only. Host app registers middleware/routes explicitly | ✓ |
| Auto-register everything | ServiceProvider auto-registers all features. Zero-config but surprising | |
| Minimal core + optional auto-register | Core ServiceProvider is minimal, separate panel provider for optional auto-registration | |

**User's choice:** Explicit opt-in
**Notes:** Laravel-conventional approach. Package provides building blocks, host app wires them. Gives host apps full control.

---

## Test Organization

| Option | Description | Selected |
|--------|-------------|----------|
| Package tests + app integration tests | Package has own `tests/` with Pest. App keeps integration tests. Both run via `composer test` | ✓ |
| All tests in app only | Simpler setup but package code has no portable tests | |
| Package unit tests only | Package has unit tests only, feature tests stay in app | |

**User's choice:** Package tests + app integration tests
**Notes:** Package tests travel with the code, ensuring independent testability for Phase A gate verification.

---

## Claude's Discretion

None — all four areas were discussed and decided by the user.

## Deferred Ideas

None — discussion stayed within phase scope.