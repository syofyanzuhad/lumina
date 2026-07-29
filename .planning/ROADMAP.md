# Roadmap: Lumina

**Project:** Lightweight self-hosted web analytics on Laravel
**Granularity:** Fine (10 phases, 5-10 plans each)
**Execution:** Parallel plans where independent
**Model:** YOLO (auto-approve)

---

## Phase A — Package-Core (Embedded Mode)

Phases 1–8 build `packages/lumina-core` as a Composer path repository within this monorepo. The standalone app (Phase B) is a thin shell built on top of it.

---

### Phase 1: Foundation & Database Schema ✅

**Goal:** Establish the data model for sites and events; confirm both Postgres and MySQL compatibility.

**Delivers:**

- `sites` migration: `id, domain, owner_id, created_at`
- `events` migration: `id, site_id, path, referrer, visitor_hash, device_type, country, created_at`
- `Site` and `Event` Eloquent models with relationships, fillable guards, casts
- Factory and seeder for both models
- Migration compatibility verified on both Postgres and MySQL (no DB-specific raw SQL)
- Pest feature tests covering model creation, relationships, and basic scopes

**Requirements covered:** DATA-01, DATA-02, DATA-03, DATA-04

---

### Phase 2: Site Management (CRUD) ✅

**Goal:** Users can register, view, and manage their sites; each site generates a tracking snippet.

**Delivers:**

- `SiteController` with index, create, store, show, destroy
- Site registration form (Vue/Inertia page) — domain input + validation
- Tracking snippet display (copy-to-clipboard UI component)
- Site list page with switcher (stores active site in session)
- Authorization policy: users can only manage their own sites
- Pest feature tests for all CRUD actions and authorization

**Requirements covered:** SITE-01, SITE-02, SITE-03, SITE-04

---

### Phase 3: Package-Core Extraction ✅

**Goal:** Extract shared logic into `packages/lumina-core` as a Composer path repository, establishing the package-core architecture that both embedded and standalone modes will consume.

**Delivers:**

- `packages/lumina-core/` directory with `composer.json` (path repository, autoload namespace `Lumina\Core`)
- Root `composer.json` updated with path repository reference to `packages/lumina-core`
- Models (`Site`, `Event`) moved to `Lumina\Core\Models\` namespace with proper Eloquent configuration
- Migrations moved to `packages/lumina-core/database/migrations/` with Laravel package migration publishing
- `LuminaCoreServiceProvider` registered in root app — publishes migrations, registers bindings
- Existing Pest tests still pass after namespace migration
- `composer require` into a throwaway test Laravel app installs migrations via `artisan vendor:publish` (manual verification, not CI yet)

**Requirements covered:** ARCH-01 (package-core architecture from project-en.md §2, §4)

---

### Phase 4: Middleware Tracking (Path A) & Metadata Migration

**Goal:** Server-side middleware tracking path (Path A) works end-to-end; `metadata` JSON column added to events table for custom events.

**Delivers:**

- `TrackPageview` middleware in `packages/lumina-core/src/Middleware/` — captures request path, referrer, visitor hash, device type, country; pushes `InsertEvent` job to queue
- Middleware registered via `LuminaCoreServiceProvider` (configurable, opt-in per route group)
- Visitor hash implementation: `hash('sha256', $ip . $userAgent . $dailySalt)` with daily salt stored in cache (rotating key)
- Country derived from IP via `geoip` lookup (or `X-Country` header from Laravel Cloud edge)
- Device type from User-Agent parsing (middleware path has no JS width available)
- Migration adding `metadata` JSON column to `events` table (nullable, null for pageviews)
- `InsertEvent` job in `packages/lumina-core/src/Jobs/` — inserts into `events` table after dequeuing
- Rate limiting: per IP (`60/min`) + per site (`300/min`) via Laravel `RateLimiter` (applies to both middleware and API paths)
- Pest feature tests: middleware tracks a request → event queued → event inserted → no raw IP stored

**Requirements covered:** INGEST-01–05, PRIV-01–04, QUEUE-01, SITE-05, ARCH-02 (middleware tracking path)

---

### Phase 5: Tracking Script (Path B) & Ingest Endpoint

**Goal:** Production-ready < 2KB vanilla JS tracking script and `POST /api/collect` ingest endpoint — the standalone/cross-domain tracking path.

**Delivers:**

- `resources/js/tracker.js` — hand-written vanilla JS, no dependencies, no imports
  - Sends `path`, `referrer`, `screen_width`, `timestamp` as JSON to `POST /api/collect`
  - Listens for Inertia `router.on('navigate')` + `history.pushState`/`popstate` for SPA navigation
  - Custom events API: `window.lumina('event_name', {props})` with nested objects allowed
  - Async, non-blocking load (`defer` attribute safe), fire-and-forget (no retry on failure)
- Build pipeline: minify via terser, gzip size verification (< 2KB gate)
- Static file served from `public/js/script.js` — CDN-friendly, zero PHP overhead
- `CollectController@store` — public, unauthenticated route at `POST /api/collect`
  - Payload validation: required fields, registered domain check (rejects unknown sites)
  - Pushes to `InsertEvent` job (same job as middleware path — shared core)
  - Rate limiter: per IP + per site (shared with middleware path)
- `Show.vue` snippet updated to reference `data-domain` attribute and `/js/script.js`
- Integration test: script loaded in headless browser fires a real request to ingest endpoint

**Requirements covered:** SCRIPT-01, SCRIPT-02, SCRIPT-03, SCRIPT-04, SCRIPT-05, INGEST-01–05

---

### Phase 6: Queue Worker & Deployment Config

**Goal:** Queue worker runs as a persistent process; Docker as a first-class deliverable for VPS deployment.

**Delivers:**

- `config/queue.php` confirmed on database driver (default for v1)
- `queue:work` under Supervisor config for VPS deployment (portable default)
- Laravel Cloud: Managed Queue as optional environment-level upgrade (not a hard dependency)
- `Dockerfile` + `docker-compose.yml` as first-class MVP deliverables (not afterthought)
  - PHP-FPM + Nginx + Supervisor managing `queue:work`
  - Postgres service in compose for local dev / VPS deploy
  - Environment-variable driven config (no hardcoded Cloud-only primitives)
- End-to-end smoke test: script installed → event received → job processed → row in `events`
- Cold-start latency measurement for Neon Postgres (first request after idle period)
- Baseline load test: `k6` or `hey`, 50 req/s × 1 min against `POST /api/collect` — p95 recorded

**Requirements covered:** QUEUE-01, QUEUE-02, DEPLOY-01, DEPLOY-02

---

### Phase 7: Aggregation Queries & Caching

**Goal:** Efficient, cached SQL aggregations for all dashboard metrics — lives in `packages/lumina-core`.

**Delivers:**

- `AnalyticsService` (or query class per metric) in `packages/lumina-core/src/Services/`
  - Pageviews total, unique visitors, top pages, top referrers, daily chart (last 30 days)
  - Custom event aggregation: flatten/select nested JSON paths from `metadata` column
- All queries use standard Eloquent / query builder (no Postgres-specific functions)
- Laravel cache wrapping each metric (60-second TTL, keyed by `site_id + date_range`)
- Cache invalidation: flushed when `InsertEvent` processes for a site (or TTL-based only — decide in plan)
- Pest feature tests: seeded events → aggregation returns correct counts matching manual SQL

**Requirements covered:** DASH-01, DASH-02, DASH-03, DASH-04, DASH-05, DASH-06, DASH-07

---

### Phase 8: Embedded Dashboard (Livewire/Filament)

**Goal:** Livewire component (or Filament panel) renders inside a host app's own layout — the Phase A presentation shell.

**Delivers:**

- Livewire component in `packages/lumina-core/src/Livewire/` (or Filament panel resource)
  - KPI cards: total pageviews, unique visitors
  - Top pages table, top referrers table
  - Daily pageview chart (line/bar, last 30 days)
  - Date filter: 7d / 30d / custom range picker
  - Empty state when no events yet
- Component consumes the shared `AnalyticsService` from Phase 7
- Renders inside host app's layout (no standalone auth, no multi-site management needed)
- Pest feature tests: component renders correct numbers for seeded data

**Requirements covered:** DASH-01–07, DATE-01–03 (embedded mode subset)

---

## ── PHASE A GATE ──

**Before Phase B starts, verify:**

- [ ] `composer require` into a throwaway test Laravel app installs migrations + middleware via a single artisan command
- [ ] Middleware tracks a real request in that test app, event lands in `events`
- [ ] Inertia SPA navigation on that test app also lands a pageview (via JS snippet's `router.on('navigate')`)
- [ ] Livewire component renders inside that test app's layout, showing real numbers
- [ ] Rollup artisan command runs on the host app's own scheduler
- [ ] Docker Compose deploy on a VPS works end-to-end (same feature set as Cloud)

---

## Phase B — Standalone App (Built on Phase A)

Phases 9–10 build the standalone product shell on top of `packages/lumina-core`.

---

### Phase 9: Standalone Dashboard UI (Inertia/Vue)

**Goal:** Vue/Inertia dashboard that displays all analytics metrics with date filtering and multi-site management.

**Delivers:**

- Dashboard page (`/dashboard` or `/{site}/dashboard`) with:
  - KPI cards: total pageviews, unique visitors
  - Top pages table, top referrers table
  - Daily pageview chart (line/bar, last 30 days) using a lightweight chart library
- Date filter UI: 7d / 30d / custom range picker
- Active site switcher in navbar/sidebar
- Empty state when no events yet (with snippet reminder)
- Responsive layout (mobile-aware)
- Multi-site management: list, switch between sites under one account
- Consumes the shared `AnalyticsService` from Phase 7 (same query layer, different presentation shell)
- Pest browser/feature tests: dashboard renders correct numbers for seeded data

**Requirements covered:** DASH-01–07, DATE-01–03, SITE-04

---

### Phase 10: End-to-End Verification & Production Readiness

**Goal:** Every MVP requirement has concrete proof as defined in §5 of project-en.md.

**Delivers:**

- Script installed on a real non-localhost site; screenshot of `SELECT count(*) FROM events WHERE site_id = X` > 0
- Dashboard numbers match manual SQL calculations for the same date range (documented evidence)
- Load test results: p95 of `/api/collect` at 50 req/s × 1 min recorded as baseline
- Queue worker verified running as persistent process on Laravel Cloud (not just config — observed running)
- Neon cold-start latency recorded
- Docker Compose deploy on a plain VPS verified end-to-end — same app, same feature set, no code branching between targets
- Package-core installed into a throwaway host app — middleware captures a real request, Livewire component renders real numbers
- All Pest tests passing (`php artisan test --compact`)
- README with self-hosting setup instructions (Docker Compose + Laravel Cloud)

**Requirements covered:** All v1 requirements final verification

---

## Backlog (Post-MVP)

These items are tracked but not in the current roadmap. Promote to v1 phases only after MVP is verified in production.

- Custom event tracking dashboard UI (V2-01)
- Data export CSV/JSON (V2-02)
- Public shareable dashboard (V2-03)
- Goal / conversion tracking (V2-04)
- Postgres table partitioning (V2-05) — only if events table bottlenecks
- ClickHouse migration (V2-06) — only if Postgres aggregations degrade at real scale
- Team support / `team_id` migration (V2-07)

---

*Roadmap created: 2026-07-26*
*Restructured: 2026-07-29 — aligned with project-en.md v1.4 (package-core architecture, Phase A/B gate, Docker deliverables, middleware tracking path, Livewire embedded dashboard)*