# Phase 3: Tracking Script - Context

**Gathered:** 2026-07-29
**Status:** Superseded by ROADMAP restructure — see note below

> **⚠️ ROADMAP Restructure (2026-07-29):** The ROADMAP was restructured from 8 flat phases to 10 phases with a Phase A/B gate. This context was originally written for "Phase 3: Tracking Script" under the old ROADMAP. Under the new structure, the Tracking Script is now **Phase 5** (merged with Ingest Endpoint). The decisions below (D-01 through D-06) remain valid and will be consumed by Phase 5 planning. The new Phase 3 is **Package-Core Extraction** — a new phase that needs its own context gathering.

<domain>
## Phase Boundary

Produce a production-ready < 2KB vanilla JS tracking script that sends pageview and custom event data to `POST /api/collect`. The script must track initial page loads AND SPA navigation (Inertia `router.on('navigate')` + `history.pushState`/`popstate`), support custom events via `window.lumina()`, and be served as a static file suitable for CDN. This phase covers the script itself, its build pipeline, and the Laravel route that serves it — NOT the ingest endpoint (Phase 4).

</domain>

<decisions>
## Script Identification
- **D-01:** Use `data-domain` attribute for site identification (e.g., `data-domain="example.com"`). The script reads this attribute to determine which site events belong to. Ingest (Phase 4) validates the domain against the `sites` table. No `tracking_token` column needed. This matches the existing `Show.vue` snippet format and keeps the snippet simple. — **Reversibility:** costly — Changing to a token later requires a migration and snippet update for all installed sites.

## Build & Serving
- **D-02:** Hand-written vanilla JS file (`resources/js/tracker.js`) with no imports, no TypeScript, no dependencies. Minified via a build step (terser or similar). This is the simplest path to staying under 2KB and avoids Vite's module wrapper overhead. — **Reversibility:** reversible
- **D-03:** Static file served from `public/js/script.js`. Build step copies the minified JS to `public/`. Served directly by the web server / CDN with zero PHP overhead. Cache busting via filename versioning or query param. — **Reversibility:** reversible

## Custom Events API
- **D-04:** Custom event props allow nested objects. `window.lumina('purchase', { product: { name: 'Widget', price: 29.99 }, quantity: 1 })` is valid. Phase 4 validation will need to handle nested JSON. Aggregation queries (Phase 6) will flatten or select specific nested paths. — **Reversibility:** costly — Restricting from nested to flat later is a breaking API change.
- **D-05 (Claude's discretion):** Custom events stored in a `metadata` JSON column on the `events` table (null for pageviews). Single table, single ingest path. Simpler schema, consistent with "avoid premature abstraction" principle. If dedicated columns or a separate table are needed later, that's a v2 migration. — **Reversibility:** reversible — Can migrate to a separate table later.

## Script Behavior (locked by project-en.md §3.1)
- **D-06 (pre-locked):** Script listens for Inertia `router.on('navigate')` events AND `history.pushState`/`popstate` for SPA navigation tracking. Fires a pageview on each virtual page change, not just initial load. This is locked by the design doc — a snippet that only fires once would undercount pageviews for the target audience (Laravel + Inertia developers).

## Claude's Discretion
- D-05 (custom event storage): JSON column on events table. User deferred this decision. Rationale: simpler schema, single ingest path, consistent with project's "avoid premature abstraction" principle. Dedicated table/columns can be added in v2 if needed.

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Design Document
- `project-en.md` §3.1 — Tracking script architecture: SPA navigation tracking, payload fields, custom events API, dual tracking paths (middleware + JS snippet)
- `project-en.md` §2 — Locked decisions: vanilla JS < 2KB, daily hash for visitor uniqueness, Inertia monolith, queue-based insert

### Requirements
- `.planning/PROJECT.md` — Core value, technical constraints, and business context
- `.planning/REQUIREMENTS.md` § Tracking Script — SCRIPT-01 through SCRIPT-05 requirements
- `.planning/REQUIREMENTS.md` § Data Model & Compatibility — DATA-01 through DATA-04 (events table schema)

### Architecture & Standards
- `.planning/codebase/CONVENTIONS.md` — Strict coding standards, ESLint, Pint, PHPStan
- `.planning/codebase/STACK.md` — Tailwind v4, Vue 3, Inertia.js, Reka UI, Lucide Vue

### Prior Phase Context
- `.planning/phases/02-site-management-crud/02-CONTEXT.md` — Domain validation, snippet delivery, site switching decisions

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `resources/js/Pages/Sites/Show.vue`: Already renders a tracking snippet with `data-domain` attribute and `src` pointing to `/js/script.js`. The script URL pattern and data-attribute approach are established.
- `app/Models/Site.php`: Has `domain` attribute with lowercase mutator. The `events()` relationship exists. No `tracking_token` column.
- `database/migrations/*_create_sites_table.php`: Schema is `id, domain (unique), owner_id, timestamps`. No token column.
- `vite.config.ts`: Existing Vite build pipeline. The tracker will NOT use this — it's a separate hand-written JS file.

### Established Patterns
- Monolith architecture using Laravel + Vue 3 + Inertia.js.
- Domain stored clean (lowercase, stripped of protocol/www) — affects how the script identifies the site.
- Inertia.js navigation: the app already uses Inertia router, so `router.on('navigate')` is available for SPA tracking.

### Integration Points
- `Show.vue` snippet template: must be updated if the script filename or data-attribute pattern changes.
- Phase 4 (`POST /api/collect`): the script sends JSON payloads to this endpoint. The payload contract (fields, nesting) is defined here but validated there.
- Phase 6 (aggregation): the `metadata` JSON column design affects how custom event props are queried later.

</code_context>

<specifics>
## Specific Ideas

- CDN-friendly serving: the script must be cacheable at the CDN/web-server level. Static file in `public/` enables this directly.
- The script should be fire-and-forget (no retry on failure) — consistent with the design doc's rate-limiting trade-off note ("events dropped by the limit = silently lost data").

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 03-Tracking Script*
*Context gathered: 2026-07-29*