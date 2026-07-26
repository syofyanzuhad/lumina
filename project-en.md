# Draft: Web Analytics Tool (Laravel + Vue + Inertia)

**Status:** Draft v1 — locked decisions marked 🔒, still open marked ❓
**Project Name:** `lumina`

---

## 1. Why this project exists

There are no analytics tools in the class of Umami/Plausible/Matomo built on Laravel. What exists are merely tracking packages (`andreaselia/laravel-analytics`, etc.) — not standalone products with their own dashboards. This is an empty niche in the Laravel ecosystem.

**Not the goal:** to beat PostHog/Matomo in terms of features. The goal is lightweight analytics, a self-hosted mindset (meaning full data and control are in your own hands), suitable for developers whose stack is already Laravel, deployed on Laravel Cloud without adding new languages/runtimes (Node, Elixir, Python).

**Risks to acknowledge upfront:** The PHP-FPM request-response model is not as natural as Node/Elixir for ingesting high-volume events. This is not a reason it won't work, but a reason **not to over-promise scale** in v1. It is designed for small-to-medium scale (blogs, small SaaS, agency client sites) — not for sites with millions of pageviews/day.

---

## 2. Locked decisions 🔒

| Area | Decision | Reasoning |
|---|---|---|
| Backend | Laravel 13 | Already the main stack, minimal context-switching |
| Starter kit | **Official Vue starter kit, Laravel 13.x** (`laravel new` → choose Vue) — Inertia 3, Vue 3 Composition API, TypeScript, Tailwind, shadcn-vue, auth via Fortify. Ref: [laravel.com/docs/13.x/starter-kits#vue-customization](https://laravel.com/docs/13.x/starter-kits#vue-customization) | The latest official starter kit (not Breeze — it's no longer the main starter kit since 13.x docs) |
| Architecture | **Fullstack monolith, one repo, one Laravel app** — not separate BE-FE | Inertia by design requires this; splitting BE-FE negates the reason to use Inertia at all and adds complexity (CORS, auth tokens, two deployment pipelines) with no concrete benefits at the MVP stage |
| TypeScript | Follow starter kit default (used) | Rejecting TS means having to strip all `.ts`/type annotations from the official scaffold — extra work with no clear benefit. Accept the default |
| Frontend | Vue 3 + Inertia.js (from starter kit) | Consistent with existing projects (Uktubuu, etc.) |
| Styling | Tailwind CSS + shadcn-vue (from starter kit) | Standard across all existing projects + included in starter kit |
| Storage (v1) | PostgreSQL (managed, Laravel Cloud) | Sufficient for MVP, avoid adding ClickHouse/Timescale dependencies before proven necessary. **Risk note:** Postgres in serverless Laravel Cloud (Neon) hibernates when idle — check cold-start latency in load tests (see §5) before assuming the collect endpoint is always fast |
| Deployment | **Fully Laravel Cloud** | Explicit decision from you — no longer VPS/Coolify like the previous draft |
| Tracking script | Vanilla JS, no dependency, target <2KB | This is a main selling point of similar tools (Plausible <1KB, lightweight Umami) |
| Dashboard Auth | Fortify (included in official starter kit) | Don't reinvent, follow the official default |
| Teams (starter kit) | **Not activated** — data model remains `owner_id` per user, not `team_id` | No validated need for multi-user per account yet. The cost of migrating to teams later if validated = a clear refactor (`owner_id` → `team_id`); the cost of activating now without proof = a permanent complexity tax (routing `/{team}/...`, scoping in all queries) for a feature that might never be used |
| Queue | Laravel queue, **database driver first** in v1, worker runs as a persistent process in Laravel Cloud | Laravel Cloud makes one-click managed Redis/Valkey easy, so upgrading to Redis later is cheap — but there is no concrete reason v1 needs Redis yet, so don't add the dependency early |

**Intentionally IGNORED for now (deferred until validated by real users):**
- Real-time live dashboard (websocket/Reverb) — v1 manual refresh/polling is sufficient
- Multi-tenant SaaS billing — v1 assumes self-hosted single-owner
- Mobile SDK, session replay, feature flags, A/B testing — outside the scope of basic analytics
- ClickHouse/columnar storage — only considered if Postgres is proven to be a bottleneck under real load

---

## 3. v1 Architecture

```
[Visitor Website]
      │  (script.js <2KB, async, no cookie)
      ▼
[POST /api/collect]  ──► validation + normalization ──► [queue: InsertEvent job]
                                                            │
                                                            ▼
                                                   [Postgres: events table]
                                                            │
[Vue/Inertia Dashboard] ◄── aggregation query (cached) ─────┘
```

### 3.1 Tracking script
- Single JS file, embedded via `<script>` tag
- Sends: Page URL, referrer, screen width (for device bucket), timestamp
- **Does NOT** send: cookies, fingerprint, raw IPs stored (daily hash + salt for unique visitors, ala Plausible/Umami — not storing IPs)
- Optional custom events: `window.lumina('event_name', {props})`

### 3.2 Ingest endpoint
- `POST /api/collect` — public endpoint, rate-limited per IP
- Minimal payload validation, reject if the domain is not registered in the `sites` table
- Push to queue job, **do not** insert directly in the request cycle (so the endpoint responds quickly to the script)
- `InsertEvent` job that inserts into Postgres

### 3.3 Storage — rough schema
```
sites          (id, domain, owner_id, created_at)
events         (id, site_id, path, referrer, visitor_hash, device_type, country, created_at)
```
- Partition the `events` table per month if volume becomes noticeable (native Postgres partitioning) — **defer until proven necessary**, do not build in v1

### 3.4 Dashboard query
- Aggregations calculated on-read with SQL queries + cache (Laravel cache, short TTL, e.g., 60 seconds) for frequently accessed endpoints (daily pageview charts, top pages, top referrers)
- **Do not** build materialized views / pre-aggregation tables in v1 — that's premature optimization before knowing real access patterns

---

## 4. MVP Scope (must work before deemed "done")

- [ ] Register 1 site, get tracking snippet
- [ ] Script installed on a page, events enter `events`
- [ ] Dashboard: total pageviews, unique visitors (per daily hash), top pages, top referrers, daily chart (last 30 days)
- [ ] Date filter (7 days / 30 days / custom range)
- [ ] Multi-site under one account (site list, switch)

**Not MVP** (do not start working on these until the above are done and verified working in real production):
- Custom event tracking dashboard UI
- Data export
- Public/shareable dashboard link
- Goal/conversion tracking

---

## 5. Verification (concrete proof, not "looks like it's done")

Each MVP item above is only considered complete if there is the following proof:
1. **Script is installed on a real site** (not localhost) and events actually enter the database — screenshot of `SELECT count(*) FROM events WHERE site_id = X` query with a number > 0
2. **Dashboard displays numbers that match** manual calculations from the `events` table for the same date range
3. **Light load test**: `/api/collect` endpoint hit with simulated load (e.g., `hey` or `k6`, 50 req/s for 1 minute) — record p95 response time, this becomes the baseline reference if a move to ClickHouse is deemed necessary later
4. **End-to-end deployment on Laravel Cloud** (real production environment, not dev environment) — including verifying the queue worker actually runs as a persistent process (not just registered in config), and recording the cold-start latency the first time serverless Postgres wakes from hibernation

---

## 6. Open questions ❓

- Final project name — undecided
- Visitor hashing scheme: daily hash (IP+UserAgent+salt, changing daily ala Plausible) or store a cookie-less session ID in localStorage? Choose one before starting coding — both are defensible, but mixing them adds complexity without clear benefits
- Is it necessary to support MySQL too (many Indonesian shared hostings default to MySQL) or is Postgres enough? This affects Eloquent compatibility but increases testing burden — recommendation: Postgres-only first, generalize later if there is real demand
- Rate limiting for `/api/collect` endpoint: per IP only, or per site too? Needs to be decided before the public endpoint is opened

---

## 7. Risks to monitor (not reasons to stop, but don't ignore)

- **PHP request-response overhead as traffic scales** — mitigation: queue jobs for inserts, not synchronous inserts. If the collect endpoint's p95 response time is > 200ms in load tests, this is a serious signal for further investigation, not just a note.
- **Postgres as an analytics store** — relational databases are not optimized for large columnar aggregations. MVP is fine for small-medium scale; if the `events` table hits tens of millions of rows and dashboard queries start to slow down, that is the decision point to move to ClickHouse/Timescale — not before.
- **Postgres serverless hibernation on Laravel Cloud** — if the database "sleeps" when idle and has to cold-start when the first event comes in after a pause, this can make the first `/api/collect` request slow or timeout if not handled (e.g., with a retry-tolerant queue). Must be verified directly in §5 point 4, do not assume it's safe.
- **Privacy/GDPR-style claims** — if later promoted as "privacy-first no cookie", ensure the visitor hash implementation is truly not reversible to an individual. This is a claim that must be proven through code, not just mentioned in marketing.

---

## 8. Why an Inertia monolith, not separate BE-FE monorepo

Laravel Cloud has a monorepo feature (attach 1 repo, create separate Cloud applications per directory — suitable for SPA frontend + API backend as two independent resources). This is **technically available** but **intentionally unused** for v1:

- Even if split into a "backend app" + "frontend app" on Laravel Cloud, the backend app remains one Laravel app containing all routes — including `/api/collect` and dashboard endpoints. **This does not solve the ingest endpoint scaling concern** which is the main risk of this project (see §7) — that's a problem to be solved at the queue/insert architecture level, not at the repo split level.
- Splitting BE-FE means throwing away Inertia entirely (replacing it with SPA + REST API + token auth), which means throwing away the official starter kit locked in §2 and adding complexity (CORS, dual deploy, version drift between BE-FE) with no concrete benefits at the MVP stage.
- This violates the GSD principle: avoid premature abstraction until there is validated real need. There is no concrete use case yet (e.g., a separate mobile app that needs to consume the same API) to justify this split.

**When to reconsider:** if there is ever a real need for a separate frontend (mobile app, separate embed widget, etc.) that requires a standalone API — that is the right decision point, not now.
