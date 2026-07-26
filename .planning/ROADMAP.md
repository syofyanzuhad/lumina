# Roadmap: Lumina

**Project:** Lightweight self-hosted web analytics on Laravel
**Granularity:** Fine (8-12 phases, 5-10 plans each)
**Execution:** Parallel plans where independent
**Model:** YOLO (auto-approve)

---

## Phase 1: Foundation & Database Schema

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

## Phase 2: Site Management (CRUD)

**Goal:** Users can register, view, and manage their sites; each site generates a unique tracking token.

**Delivers:**

- `SiteController` with index, create, store, show, destroy
- Site registration form (Vue/Inertia page) — domain input + validation
- Tracking snippet display (copy-to-clipboard UI component)
- Site list page with switcher (stores active site in session)
- Authorization policy: users can only manage their own sites
- Pest feature tests for all CRUD actions and authorization

**Requirements covered:** SITE-01, SITE-02, SITE-03, SITE-04

---

## Phase 3: Tracking Script

**Goal:** Produce a production-ready < 2KB vanilla JS tracking script.

**Delivers:**

- `script.js` (vanilla JS, no dependencies): sends `path`, `referrer`, `screen_width`, `timestamp` as JSON to `POST /api/collect`
- Async, non-blocking load (`defer` attribute safe)
- Custom events API: `window.lumina('event_name', {props})`
- Build pipeline: minify + gzip size verification (< 2KB gate in CI)
- Script served from a public Laravel route (`/lumina.js`)
- Integration test: script loaded in a headless browser fires a real request to the ingest endpoint

**Requirements covered:** SCRIPT-01, SCRIPT-02, SCRIPT-03, SCRIPT-04, SCRIPT-05

---

## Phase 4: Event Ingest & Privacy

**Goal:** `POST /api/collect` ingests events safely, quickly, and without storing raw IPs.

**Delivers:**

- `CollectController@store` — public, unauthenticated route
- Payload validation: required fields, registered domain check (rejects unknown sites)
- Rate limiter: per IP (`60/min`) + per site (`300/min`) via Laravel `RateLimiter`
- `InsertEvent` job: inserts into `events` table after dequeuing
- Visitor hash: `hash('sha256', $ip . $userAgent . $dailySalt)` — daily salt stored in cache (rotating key)
- Country derived from IP via `geoip` lookup (or X-Country header from Laravel Cloud edge); device type from screen width bucket
- No raw IPs stored anywhere in the pipeline
- Pest feature tests: valid event → queued, invalid domain → 422, rate limit → 429, hash non-reversibility assertion

**Requirements covered:** INGEST-01–05, PRIV-01–04, QUEUE-01, SITE-05

---

## Phase 5: Queue Worker & Deployment Config

**Goal:** Queue worker runs as a persistent process on Laravel Cloud; verified end-to-end.

**Delivers:**

- `config/queue.php` confirmed on database driver
- `horizon.php` or plain worker process config for Laravel Cloud persistent worker
- Laravel Cloud deployment manifest / environment docs
- End-to-end smoke test: script installed → event received → job processed → row in `events`
- Cold-start latency measurement for Neon Postgres (first request after idle period)
- Baseline load test: `k6` or `hey`, 50 req/s × 1 min against `POST /api/collect` — p95 recorded

**Requirements covered:** QUEUE-01, QUEUE-02

---

## Phase 6: Aggregation Queries & Caching

**Goal:** Efficient, cached SQL aggregations for all dashboard metrics.

**Delivers:**

- `AnalyticsService` (or query class per metric): pageviews total, unique visitors, top pages, top referrers, daily chart (last 30 days)
- All queries use standard Eloquent / query builder (no Postgres-specific functions)
- Laravel cache wrapping each metric (60-second TTL, keyed by `site_id + date_range`)
- Cache invalidation: flushed when `events` are inserted for a site (or TTL-based only — decide in plan)
- Pest feature tests: seeded events → aggregation returns correct counts matching manual SQL

**Requirements covered:** DASH-01, DASH-02, DASH-03, DASH-04, DASH-05, DASH-06, DASH-07

---

## Phase 7: Dashboard UI

**Goal:** Vue/Inertia dashboard that displays all analytics metrics with date filtering.

**Delivers:**

- Dashboard page (`/dashboard` or `/{site}/dashboard`) with:
  - KPI cards: total pageviews, unique visitors
  - Top pages table
  - Top referrers table
  - Daily pageview chart (line/bar, last 30 days) using a lightweight chart library
- Date filter UI: 7d / 30d / custom range picker
- Active site switcher in navbar/sidebar
- Empty state when no events yet (with snippet reminder)
- Responsive layout (mobile-aware)
- Pest browser/feature tests: dashboard renders correct numbers for seeded data

**Requirements covered:** DASH-01–07, DATE-01–03, SITE-04

---

## Phase 8: End-to-End Verification & Production Readiness

**Goal:** Every MVP requirement has concrete proof as defined in §5 of project-en.md.

**Delivers:**

- Script installed on a real non-localhost site; screenshot of `SELECT count(*) FROM events WHERE site_id = X` > 0
- Dashboard numbers match manual SQL calculations for the same date range (documented evidence)
- Load test results: p95 of `/api/collect` at 50 req/s × 1 min recorded as baseline
- Queue worker verified running as persistent process on Laravel Cloud (not just config — observed running)
- Neon cold-start latency recorded
- All Pest tests passing (run `php artisan test --compact`)
- README with self-hosting setup instructions

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
*Last updated: 2026-07-26 after initialization*
