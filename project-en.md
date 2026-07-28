# Design Doc: Lumina — Web Analytics Tool (Laravel + Vue + Inertia)

**Status:** Draft v1.4 — locked decisions marked 🔒, still open marked ❓
**Project name:** Lumina

> Revision history: v1.1 flagged two unresolved conflicts (deployment target; standalone-vs-package architecture) — resolved in v1.2. v1.3 locks the package repo strategy (stay monorepo, subtree-split only if/when published) while leaving the Packagist-publish timing itself genuinely open — see §6. v1.4 adds a reference note on the feature-parity monetization model under the Deployment row (§2) — external evidence, not a new decision.

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
| Distribution model | 🔒 **Package-core first, standalone app as a shell built on top of it — sequenced, not parallel** | Resolved: both an embeddable Composer package (server-side middleware tracking, no JS needed for host-app pages, Livewire/Filament dashboard component) and a standalone product are wanted, but building both as independent parallel features in v1 would double the tracking paths, auth models, and distribution channels before either is validated — a direct violation of this project's own "avoid premature abstraction" principle. Instead: `packages/lumina-core` (path-repository Composer package within the same repo) holds all the actual logic — models, migrations, middleware, rollup command, Livewire component/Filament panel. The "standalone app" described in §3 is simply the reference Laravel app in this same repo installing and dogfooding that package, plus a thin additional layer (multi-site management table, its own Fortify auth, a public JS-snippet-served endpoint for tracking non-Laravel or cross-domain sites). This means the package is proven in production the moment the standalone app ships — not a separate future effort. |
| TypeScript | Kept as the starter kit default | Rejecting TS means stripping all `.ts`/type annotations from the official scaffold — extra work for no clear benefit. Accept the default |
| Frontend | Vue 3 + Inertia.js (from the starter kit) | Consistent with other projects (Uktubuu, etc.) |
| Styling | Tailwind CSS + shadcn-vue (from the starter kit) | Standard across existing projects + starter kit default |
| Storage (v1) | PostgreSQL (managed, Laravel Cloud) | Sufficient for MVP; avoid adding ClickHouse/Timescale as a dependency before there's evidence it's needed. **Risk note, now verified [High confidence, per Laravel Cloud docs]:** Laravel Serverless Postgres on Laravel Cloud is Neon-powered and hibernates on an idle timeout — but the timeout is configurable (0–30 days retention, adjustable hibernation timeout under Resources → Databases → Backups), not a fixed default you're stuck with. Two mitigations worth testing before assuming ClickHouse is the fallback: (1) set the hibernation timeout long enough that a realistic traffic pattern never actually triggers cold start, or (2) use **Laravel MySQL** instead for the `events` table specifically — Laravel Cloud docs describe it as a non-hibernating alternative (it "archives" rather than sleeping). Still verify actual cold-start latency in the load test (§5) rather than assuming either mitigation is sufficient. |
| Deployment | 🔒 **Laravel Cloud + any VPS, feature parity (Coolify model)** | Resolved: self-hosted and any managed offering (if one ever exists) run the identical codebase — no feature gating, monetize hosting convenience only, not features. **Concrete implementation consequences, not just a policy statement:** (1) the repo needs a `Dockerfile` + `docker-compose.yml` as first-class MVP deliverables (not an afterthought) so a VPS deploy via Coolify/plain Docker is a real, tested path, not just a theoretical one; (2) nothing in the core app can hard-depend on a Laravel-Cloud-only primitive — **Managed Queues become an optional, Cloud-specific optimization** layered on top of the portable default (`queue:work` under Supervisor works identically on a VPS; Managed Queue is an env-driven upgrade when deploying to Cloud specifically, not a required architecture); (3) Neon-hibernation tuning (Storage row above) is Cloud-only infra config — a self-hosted Postgres/MySQL on a VPS simply doesn't hibernate, so that mitigation section doesn't need a VPS equivalent, it just doesn't apply there. **Reference note on the "why would anyone pay for managed if features are identical" question raised earlier in this project's discussion:** Jack Ellis (founder of Fathom Analytics — one of this project's own comparison points) has stated publicly that despite a one-click self-hosted Fathom setup existing on DigitalOcean for years, it didn't meaningfully cut into paid signups — his read is that only a small share of the market wants to self-host regardless of how easy that's made, and separately lists several categories of self-hostable software he personally pays to have hosted (version control, calendar booking, error tracking, form signing). [Medium confidence: single first-hand anecdote from a founder with real revenue visibility, not a systematic study — supports that the feature-parity monetization model *can* work, but Fathom's traction also came with years of content marketing and positioning, so this validates the model, not Lumina's execution or distribution plan, which still needs its own answer.] |
| Tracking script | Vanilla JS, no dependencies, target <2KB — **one of two tracking paths, not the only one** | This is the core selling point of comparable tools (Plausible <1KB, Umami lightweight) for the standalone app's non-Laravel/cross-domain use case. **For the package-core (embedded) path, this is not the primary mechanism** — a Laravel app with `lumina-core` installed tracks server-side via middleware by default, no JS required for its own pages. The JS snippet is still needed in two narrower cases: (a) tracking a page on a *different* domain than the one running Lumina (the standalone app's core use case), and (b) capturing client-side SPA navigation within an Inertia app even when middleware handles the initial load — see §3.1. |
| Auth (dashboard) | Fortify (bundled with the official starter kit) | Don't reinvent it, use the official default |
| Teams (starter kit) | **Not enabled** — data model stays `owner_id` per user, not `team_id` | No validated need for multi-user accounts yet. Migrating to teams later if validated = a well-defined refactor (`owner_id` → `team_id`); enabling it now without evidence = a permanent complexity tax (`/{team}/...` routing, scoping in every query) for a feature that may never be used |
| Visitor uniqueness | **Daily hash** (IP + UserAgent + daily salt, rotated every day) — not a localStorage ID | Consistent with the "no cookie/no persistent client storage" claim (§1); localStorage is functionally a persistent identifier even if not a technical cookie, and is prone to being blocked by privacy-focused browsers. **Trade-off knowingly accepted:** the "unique visitors over 7/30 days" metric becomes a sum of daily-uniques, not a true distinct-human count across the period — the same limitation Plausible/Umami have. Dashboard copy must be honest about this, not quietly glossed over |
| Rate limiting | **Per-IP + per-site** on `/api/collect` | Per-IP alone doesn't stop a distributed flood against one site, or a noisy-neighbor site exhausting shared queue/DB capacity. This isn't premature abstraction — an unauthenticated public endpoint needs baseline protection from day one. **Honest caveat:** initial thresholds are still a guess (needs recalibration using the §5.3 load test results); events dropped by the limit = silently lost data (the script is fire-and-forget, no retry from the visitor) — this trade-off must be documented, not hidden |
| Database scope | **PostgreSQL (production, Laravel Cloud) + MySQL (local dev)** — dual-engine, no longer Postgres-only | Direct requirement: local dev on MySQL, production on Postgres. **Real dev/prod parity risk** (not just theoretical): string-comparison case sensitivity differs by default between engines (relevant to the `domain` lookup in `sites` — normalize to lowercase explicitly before querying, don't rely on default collation), boolean/JSON representation differs too. **Mandatory, not optional:** (1) migrations & queries must be pure Eloquent, avoid raw SQL or engine-specific features; (2) before every production deploy, run migrations + a minimal smoke test against Postgres at least once (local Docker container or CI) — "works locally on MySQL" alone is not sufficient evidence |
| Queue | Laravel queue, **database driver first** in v1, `queue:work` under Supervisor as the portable default; **Laravel Cloud Managed Queue as an optional Cloud-specific upgrade**, not a hard dependency | Laravel Cloud makes managed Redis/Valkey a one-click add, so upgrading later is cheap — but there's no concrete reason v1 needs Redis yet, so don't add the dependency upfront. **Correction to an earlier assumption in this project's discussion:** for Laravel apps specifically, a sleeping (scale-to-zero) environment auto-wakes to run scheduled tasks and process queued jobs — this is *not* a Symfony-only limitation as previously feared, and Laravel Cloud docs confirm nothing falls behind while the environment sleeps. The one real remaining risk on Cloud: if a job is mid-execution when the sleep timeout hits, the App cluster stops and the job can be interrupted. **Recommended concrete action:** when deployed to Laravel Cloud specifically, move the `InsertEvent` job (and the rollup job) to a Managed Queue, since Managed Queues are documented as immune to this interruption. On a self-hosted VPS this risk doesn't exist in the same form (no scale-to-zero), so Supervisor-managed `queue:work` is sufficient there — consistent with the feature-parity deployment decision above: same codebase, the queue *driver* choice is an environment-level config difference, not a fork in the app's logic. |

**Deliberately DEFERRED (until real user validation exists):**
- Real-time live dashboard (websocket/Reverb) — v1 is fine with manual refresh/polling
- Multi-tenant SaaS billing — v1 assumes self-hosted, single-owner
- Mobile SDK, session replay, feature flags, A/B testing — outside the scope of basic analytics
- ClickHouse/columnar storage — only reconsidered once Postgres proves to be a bottleneck under real load

---

## 3. v1 Architecture

```
Path A — embedded (package-core, host app's own pages)      Path B — standalone (cross-domain / SPA-nav)
[Host Laravel app request]                                   [Website visitor / Inertia navigation]
      │  (middleware, server-side, no JS)                          │  (script.js <2KB, async, no cookie)
      ▼                                                             ▼
[lumina-core middleware] ──► normalization ──► [queue: InsertEvent job]  ◄── [POST /api/collect] ◄── validation
                                                            │
                                                            ▼
                                                   [Postgres/MySQL: events table]
                                                            │
                              [Livewire component / Filament panel] ◄── aggregation query (cached)
                              (embedded in host app)              (or Vue/Inertia dashboard in standalone app)
```

Both paths converge on the same `InsertEvent` job and the same `events` table — the package (`packages/lumina-core`) owns this shared core. The standalone app is this package installed into a reference Laravel app, plus the JS-snippet delivery route and multi-site management described below.

### 3.1 Tracking script
- A single JS file, embedded via a `<script>` tag — used for Path B (cross-domain tracking in standalone mode) and, within Path A, specifically for SPA route-change events an Inertia app's middleware alone can't see
- Sends: page URL, referrer, screen width (for device bucketing), timestamp
- **Does not** send: cookies, fingerprints, raw stored IP (daily salted hash for unique visitors, à la Plausible/Umami — not raw IP storage)
- Optional custom event: `window.lumina('event_name', {props})`
- 🔒 **Locked:** the script explicitly listens for Inertia's `router.on('navigate', ...)` event (with an equivalent `history.pushState`/`popstate` hook for non-Inertia SPAs) and fires a pageview on each virtual page change, in addition to the initial load. A snippet that only fires once on page load would undercount pageviews specifically for this project's own target audience (Laravel + Inertia developers) — not acceptable to defer.

### 3.2 Ingest endpoint (Path B — standalone/cross-domain)
- `POST /api/collect` — public endpoint, rate-limited per IP
- Minimal payload validation, reject if the domain isn't registered in the `sites` table
- Push to a queue job, **don't** insert synchronously in the request cycle (so the endpoint stays fast for the script)
- `InsertEvent` job handles the actual Postgres/MySQL insert
- **Path A (embedded) equivalent:** the `lumina-core` middleware performs the same validation + queue-push in-process, without an HTTP round-trip to a separate endpoint — it's registered directly in the host app's middleware stack. No public endpoint or `sites`-table lookup is needed for a single embedded install; multi-site scoping only applies to the standalone app's own data model.

### 3.3 Storage — rough schema
```
sites          (id, domain, owner_id, created_at)
events         (id, site_id, path, referrer, visitor_hash, device_type, country, created_at)
```
- Partition the `events` table monthly once volume starts to matter (native Postgres partitioning) — **defer until there's evidence it's needed**, don't build this in v1

### 3.4 Dashboard queries
- Aggregations computed on-read via SQL + cache (Laravel cache, short TTL, e.g. 60s) for frequently-hit endpoints (daily pageview chart, top pages, top referrers)
- **Don't** build a materialized view / pre-aggregation table in v1 — that's premature optimization before real access patterns are known
- This aggregation layer lives in `packages/lumina-core` and is consumed by both the Livewire component/Filament panel (Path A, embedded) and the standalone app's Vue/Inertia dashboard (Path B) — one query layer, two presentation shells. Don't duplicate the aggregation logic per shell.

---

## 4. MVP scope (must work before calling it "done")

Sequenced per §2's distribution-model decision — **Phase A must be verified working before Phase B starts.** Building both phases in parallel is exactly the premature-scope-expansion this project's own GSD principle warns against.

**Phase A — `packages/lumina-core` (package, embedded mode)**
- [ ] `composer require` into a throwaway test Laravel app installs migrations + middleware via a single artisan command
- [ ] Middleware tracks a real request in that test app, event lands in `events`
- [ ] Inertia SPA navigation on that test app also lands a pageview (via the JS snippet's `router.on('navigate')` listener — §3.1)
- [ ] Livewire component (or Filament panel) renders inside that test app's own layout, showing pageviews/unique visitors/top pages pulled from the shared aggregation layer (§3.4)
- [ ] Rollup artisan command runs on the host app's own scheduler

**Phase B — standalone app (shell built on Phase A)**
- [ ] Register 1 site, get a tracking snippet
- [ ] Script installed on a page, events land in `events` via `/api/collect`
- [ ] Dashboard: total pageviews, unique visitors (daily-hash based), top pages, top referrers, daily chart (last 30 days)
- [ ] Date filter (7 days / 30 days / custom range)
- [ ] Multiple sites under one account (list, switch)
- [ ] Deploys identically via Docker Compose on a VPS and via Laravel Cloud (§2 deployment decision) — both verified, not assumed

**Not MVP** (don't start on these before Phase A **and** Phase B above are done and verified working in production):
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
5. **End-to-end deploy on a plain VPS via Docker Compose** — same app, same feature set, no code branching between the two targets. This is the concrete evidence for the feature-parity deployment decision (§2) — "should work on a VPS" isn't sufficient, it has to actually be deployed and checked
6. **Package-core installed into a throwaway host app** (Phase A) — middleware captures a real request, Inertia nav capture confirmed on that same test app, Livewire component renders real numbers. This is the evidence gate before Phase B (standalone shell) work starts

---

## 6. Open questions ❓

All open questions from the earlier draft have been decided (name: **Lumina**, visitor uniqueness, rate limiting, database scope — see §2). The two items reopened in the previous revision are now resolved:

1. ~~Deployment target~~ — **Resolved:** Laravel Cloud + any VPS, feature parity, sequenced deployment verification in §5. See §2's Deployment row for the concrete implementation consequences (Docker artifacts, portable queue default).
2. ~~Standalone vs package architecture~~ — **Resolved:** both, sequenced — package-core first (Phase A), standalone shell built on top of it (Phase B). See §2's Distribution model row and the resequenced §4.

**New, smaller open item surfaced by this resolution:**

3. 🔒 **Composer package repo/publishing strategy — resolved.** `packages/lumina-core` stays a path repository inside this same monorepo indefinitely for development — no separate repo now. Precedent: `laravel/framework` develops all `illuminate/*` packages in one monorepo and only subtree-splits them into separate public repos for Packagist distribution, via automation, not as the day-to-day development model. If/when `lumina-core` is published on Packagist (still genuinely open — v1 or later, unresolved on purpose), the answer is "add a subtree-split GitHub Action," not "move the source to a new repo." This avoids paying monorepo-split coordination costs (double PRs, double CI, premature semver discipline) before there's a single external consumer who needs that stability.

---

## 7. Risks to monitor (not reasons to stop, but not to ignore either)

- **PHP request-response overhead under rising traffic** — mitigation: queue the insert job, don't insert synchronously. If p95 response time on the collect endpoint exceeds 200ms in the load test, that's a serious signal to investigate further, not just a note to file away.
- **Postgres as the analytics store** — relational DBs aren't optimized for large-column aggregation. Fine for MVP at small-to-medium scale; once the `events` table hits tens of millions of rows and dashboard queries start slowing down, that's the decision point to move to ClickHouse/Timescale — not before.
- **Postgres serverless hibernation on Laravel Cloud** — if the database "sleeps" when idle and has to cold-start when the next event comes in, this could make the first `/api/collect` request slow or time out if not handled (e.g. with a retry-tolerant queue). Needs to be verified directly per §5 item 4 — don't assume it's fine. [Update, verified via Laravel Cloud docs: hibernation timeout is configurable, and Laravel MySQL is a documented non-hibernating alternative if this proves to be a real problem — see the Storage row in §2. This downgrades the risk from "unknown severity" to "known, has two concrete mitigations to test," but the load test in §5 is still the only way to know if either mitigation is actually necessary.]
- **Compute scale-to-zero + scheduled/queued work** — *(superseded)* this was previously flagged as a risk on the assumption that scale-to-zero breaks scheduled tasks. Verified false for Laravel apps specifically: Laravel Cloud auto-wakes sleeping environments to run scheduled tasks and process queued jobs. The real, narrower risk is a job being interrupted mid-execution if it's still running when the sleep timeout hits — mitigated by using Managed Queues (see the Queue row in §2) rather than a self-managed worker on the App cluster.
- **Privacy/GDPR-style claims** — if this is ever marketed as "privacy-first, no cookies," the visitor hashing implementation needs to genuinely be non-reversible to an individual. That's a claim that has to be proven in code, not just stated in marketing copy.
- **Dual distribution mode (package + standalone) doubles the long-term maintenance surface** — even sequenced (§2, §4), every future feature added to `lumina-core` needs to work correctly in both an embedded Livewire/Filament context and a standalone Inertia context. This is an accepted, deliberate trade-off given the differentiation argument for the Laravel-community niche (§1) — but it's a real, ongoing cost, not a one-time setup cost. Worth revisiting after Phase A ships: if the embedded mode sees no real adoption, consider whether maintaining both shells is still worth it, rather than assuming it automatically is.

---

## 8. Why an Inertia monolith, not a separate BE-FE monorepo

Laravel Cloud has monorepo support (attach one repo, create separate Cloud applications per directory — suited to a frontend SPA + backend API as two independent resources). This is **technically available** but **deliberately not used** for v1:

- Even if split into a "backend app" + "frontend app" on Laravel Cloud, the backend app is still a single Laravel app containing all routes — including `/api/collect` and the dashboard endpoints. **This does not solve the ingest endpoint scaling concern** that's the project's main risk (see §7) — that's a problem to solve at the queue/insert architecture level, not by splitting repos.
- Splitting BE-FE means dropping Inertia entirely (switching to SPA + REST API + token auth), which means dropping the official starter kit locked in §2, and adds complexity (CORS, dual deploys, BE-FE version drift) with no concrete MVP-stage benefit.
- This violates the GSD principle: avoid premature abstraction until there's real validated need. There's no concrete use case yet (e.g. a separate mobile app needing to consume the same API) that would justify this split.

**When to reconsider:** if there's ever a real need for a separate frontend (mobile app, standalone embeddable widget, etc.) that requires a standalone API — that's the right decision point, not now.

**Note on `packages/lumina-core` (§2, §4):** this is a Composer *path repository* inside the same monolith repo, not a BE-FE split or a separately deployed service — it doesn't reopen this section's argument. The standalone app still deploys as a single Laravel app; the package boundary just keeps the tracking/aggregation logic reusable by a third party's host app later, without duplicating code between Phase A and Phase B.
