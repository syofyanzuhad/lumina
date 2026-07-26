# Design Doc: Lumina — Web Analytics Tool (Laravel + Vue + Inertia)

**Status:** Draft v1 — locked decisions marked 🔒, still open marked ❓
**Project name:** Lumina

---

## 1. Why this project exists

There's no analytics tool in the same class as Umami/Plausible/Matomo built on Laravel. What exists are tracking packages (`andreaselia/laravel-analytics`, etc.) — not a standalone product with its own dashboard. This is an empty niche in the Laravel ecosystem.

**Not the goal:** beating PostHog/Matomo on features. The goal is lightweight, self-hosted-minded analytics (meaning full data and control stays in your own hands), for developers already on a Laravel stack, deployed on Laravel Cloud without adding a new language/runtime (Node, Elixir, Python).

**Risk to acknowledge upfront:** the PHP-FPM request-response model isn't as naturally suited to high-volume event ingestion as Node/Elixir. That's not a reason not to build this, but it is a reason to **not over-promise scale** in v1. Designed for small-to-medium scale (blogs, small SaaS, agency client sites) — not sites doing millions of pageviews/day.

---

## 2. Locked decisions 🔒

| Area | Decision | Rationale |
|---|---|---|
| Backend | Laravel 13 | Already the primary stack, minimal context-switching |
| Starter kit | **Official Vue starter kit, Laravel 13.x** (`laravel new` → select Vue) — Inertia 3, Vue 3 Composition API, TypeScript, Tailwind, shadcn-vue, auth via Fortify. Ref: [laravel.com/docs/13.x/starter-kits#vue-customization](https://laravel.com/docs/13.x/starter-kits#vue-customization) | This is the current official starter kit (not Breeze — Breeze is no longer the primary starter kit as of the 13.x docs) |
| Architecture | **Fullstack monolith, single repo, single Laravel app** — not a separate BE-FE split | Inertia's design assumes this; splitting BE-FE defeats the point of using Inertia at all and adds complexity (CORS, token auth, dual deploy pipelines) with no concrete MVP-stage benefit |
| TypeScript | Kept as the starter kit default | Rejecting TS means stripping all `.ts`/type annotations from the official scaffold — extra work for no clear benefit. Accept the default |
| Frontend | Vue 3 + Inertia.js (from the starter kit) | Consistent with other projects (Uktubuu, etc.) |
| Styling | Tailwind CSS + shadcn-vue (from the starter kit) | Standard across existing projects + starter kit default |
| Storage (v1) | PostgreSQL (managed, Laravel Cloud) | Sufficient for MVP; avoid adding ClickHouse/Timescale as a dependency before there's evidence it's needed. **Risk note:** Postgres on Laravel Cloud is serverless (Neon) and hibernates when idle — verify cold-start latency in the load test (see §5) before assuming the collect endpoint is always fast |
| Deployment | **Laravel Cloud, fully** | Explicit decision from you — no longer VPS/Coolify as in the earlier draft |
| Tracking script | Vanilla JS, no dependencies, target <2KB | This is the core selling point of comparable tools (Plausible <1KB, Umami lightweight) |
| Auth (dashboard) | Fortify (bundled with the official starter kit) | Don't reinvent it, use the official default |
| Teams (starter kit) | **Not enabled** — data model stays `owner_id` per user, not `team_id` | No validated need for multi-user accounts yet. Migrating to teams later if validated = a well-defined refactor (`owner_id` → `team_id`); enabling it now without evidence = a permanent complexity tax (`/{team}/...` routing, scoping in every query) for a feature that may never be used |
| Visitor uniqueness | **Daily hash** (IP + UserAgent + daily salt, rotated every day) — not a localStorage ID | Consistent with the "no cookie/no persistent client storage" claim (§1); localStorage is functionally a persistent identifier even if not a technical cookie, and is prone to being blocked by privacy-focused browsers. **Trade-off knowingly accepted:** the "unique visitors over 7/30 days" metric becomes a sum of daily-uniques, not a true distinct-human count across the period — the same limitation Plausible/Umami have. Dashboard copy must be honest about this, not quietly glossed over |
| Rate limiting | **Per-IP + per-site** on `/api/collect` | Per-IP alone doesn't stop a distributed flood against one site, or a noisy-neighbor site exhausting shared queue/DB capacity. This isn't premature abstraction — an unauthenticated public endpoint needs baseline protection from day one. **Honest caveat:** initial thresholds are still a guess (needs recalibration using the §5.3 load test results); events dropped by the limit = silently lost data (the script is fire-and-forget, no retry from the visitor) — this trade-off must be documented, not hidden |
| Database scope | **PostgreSQL (production, Laravel Cloud) + MySQL (local dev)** — dual-engine, no longer Postgres-only | Direct requirement: local dev on MySQL, production on Postgres. **Real dev/prod parity risk** (not just theoretical): string-comparison case sensitivity differs by default between engines (relevant to the `domain` lookup in `sites` — normalize to lowercase explicitly before querying, don't rely on default collation), boolean/JSON representation differs too. **Mandatory, not optional:** (1) migrations & queries must be pure Eloquent, avoid raw SQL or engine-specific features; (2) before every production deploy, run migrations + a minimal smoke test against Postgres at least once (local Docker container or CI) — "works locally on MySQL" alone is not sufficient evidence |
| Queue | Laravel queue, **database driver first** in v1, worker runs as a persistent process on Laravel Cloud | Laravel Cloud makes managed Redis/Valkey a one-click add, so upgrading later is cheap — but there's no concrete reason v1 needs Redis yet, so don't add the dependency upfront |

**Deliberately DEFERRED (until real user validation exists):**
- Real-time live dashboard (websocket/Reverb) — v1 is fine with manual refresh/polling
- Multi-tenant SaaS billing — v1 assumes self-hosted, single-owner
- Mobile SDK, session replay, feature flags, A/B testing — outside the scope of basic analytics
- ClickHouse/columnar storage — only reconsidered once Postgres proves to be a bottleneck under real load

---

## 3. v1 Architecture

```
[Website visitor]
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
- A single JS file, embedded via a `<script>` tag
- Sends: page URL, referrer, screen width (for device bucketing), timestamp
- **Does not** send: cookies, fingerprints, raw stored IP (daily salted hash for unique visitors, à la Plausible/Umami — not raw IP storage)
- Optional custom event: `window.lumina('event_name', {props})`

### 3.2 Ingest endpoint
- `POST /api/collect` — public endpoint, rate-limited per IP
- Minimal payload validation, reject if the domain isn't registered in the `sites` table
- Push to a queue job, **don't** insert synchronously in the request cycle (so the endpoint stays fast for the script)
- `InsertEvent` job handles the actual Postgres insert

### 3.3 Storage — rough schema
```
sites          (id, domain, owner_id, created_at)
events         (id, site_id, path, referrer, visitor_hash, device_type, country, created_at)
```
- Partition the `events` table monthly once volume starts to matter (native Postgres partitioning) — **defer until there's evidence it's needed**, don't build this in v1

### 3.4 Dashboard queries
- Aggregations computed on-read via SQL + cache (Laravel cache, short TTL, e.g. 60s) for frequently-hit endpoints (daily pageview chart, top pages, top referrers)
- **Don't** build a materialized view / pre-aggregation table in v1 — that's premature optimization before real access patterns are known

---

## 4. MVP scope (must work before calling it "done")

- [ ] Register 1 site, get a tracking snippet
- [ ] Script installed on a page, events land in `events`
- [ ] Dashboard: total pageviews, unique visitors (daily-hash based), top pages, top referrers, daily chart (last 30 days)
- [ ] Date filter (7 days / 30 days / custom range)
- [ ] Multiple sites under one account (list, switch)

**Not MVP** (don't start on these before the above is done and verified working in production):
- Custom event tracking dashboard UI
- Data export
- Public/shareable dashboard link
- Goal/conversion tracking

---

## 5. Verification (concrete evidence, not "looks done to me")

Each MVP item above is only considered done with the following evidence:
1. **Script installed on a real site** (not localhost) and events are actually landing in the database — screenshot of `SELECT count(*) FROM events WHERE site_id = X` showing a number > 0
2. **Dashboard numbers match** a manual count from the `events` table for the same date range
3. **Light load test**: hit `/api/collect` with simulated load (e.g. `hey` or `k6`, 50 req/s for 1 minute) — record p95 response time; this becomes the reference baseline for any future decision to move to ClickHouse
4. **End-to-end deploy on Laravel Cloud** (real production environment, not dev) — including verifying the queue worker actually runs as a persistent process (not just registered in config), and recording the cold-start latency the first time the serverless Postgres wakes from hibernation

---

## 6. Open questions ❓

All open questions from the earlier draft have been decided (name: **Lumina**, visitor uniqueness, rate limiting, database scope — see §2). No open questions remain right now; add new ones here as they come up during implementation.

---

## 7. Risks to monitor (not reasons to stop, but not to ignore either)

- **PHP request-response overhead under rising traffic** — mitigation: queue the insert job, don't insert synchronously. If p95 response time on the collect endpoint exceeds 200ms in the load test, that's a serious signal to investigate further, not just a note to file away.
- **Postgres as the analytics store** — relational DBs aren't optimized for large-column aggregation. Fine for MVP at small-to-medium scale; once the `events` table hits tens of millions of rows and dashboard queries start slowing down, that's the decision point to move to ClickHouse/Timescale — not before.
- **Postgres serverless hibernation on Laravel Cloud** — if the database "sleeps" when idle and has to cold-start when the next event comes in, this could make the first `/api/collect` request slow or time out if not handled (e.g. with a retry-tolerant queue). Needs to be verified directly per §5 item 4 — don't assume it's fine.
- **Privacy/GDPR-style claims** — if this is ever marketed as "privacy-first, no cookies," the visitor hashing implementation needs to genuinely be non-reversible to an individual. That's a claim that has to be proven in code, not just stated in marketing copy.

---

## 8. Why an Inertia monolith, not a separate BE-FE monorepo

Laravel Cloud has monorepo support (attach one repo, create separate Cloud applications per directory — suited to a frontend SPA + backend API as two independent resources). This is **technically available** but **deliberately not used** for v1:

- Even if split into a "backend app" + "frontend app" on Laravel Cloud, the backend app is still a single Laravel app containing all routes — including `/api/collect` and the dashboard endpoints. **This does not solve the ingest endpoint scaling concern** that's the project's main risk (see §7) — that's a problem to solve at the queue/insert architecture level, not by splitting repos.
- Splitting BE-FE means dropping Inertia entirely (switching to SPA + REST API + token auth), which means dropping the official starter kit locked in §2, and adds complexity (CORS, dual deploys, BE-FE version drift) with no concrete MVP-stage benefit.
- This violates the GSD principle: avoid premature abstraction until there's real validated need. There's no concrete use case yet (e.g. a separate mobile app needing to consume the same API) that would justify this split.

**When to reconsider:** if there's ever a real need for a separate frontend (mobile app, standalone embeddable widget, etc.) that requires a standalone API — that's the right decision point, not now.
