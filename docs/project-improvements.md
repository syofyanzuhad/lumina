# Lumina — Project Improvement Analysis

> Generated from a full codebase review (August 2026). Every item below is grounded in the
> actual repository state — files, configs, and failing tests — not generic advice.
> Items are grouped by priority and dependency order; each has a concrete "why" and a
> suggested approach.

**Current state at a glance**

- Milestones v1.0 (Phases 1–10) and v1.1 (Phases 11–16) shipped; v1.2 not yet planned.
- Tracking pipeline recently hardened: terminable middleware, privacy-first opaque identity,
  stable-salt fallback hashing, atomic rate limiting, idempotent queue inserts.
- **Full test suite: 136 passing / 37 failing locally** — every failure is environmental
  (see 🔴 #1). CI (SQLite) is green.
- No scheduled tasks, no data-retention policy, no frontend test framework, no webhooks.

---

## 🔴 Critical — fix first (blockers & correctness)

### 1. Local test environment diverges from CI — 37 tests fail on dev machines

**Problem:** `phpunit.xml` configures `DB_CONNECTION=sqlite` / `:memory:`, but the local
`.env` forces `DB_CONNECTION=mysql` (database `lumina`). Because a real `.env` overrides the
phpunit `<env>` entries, every `RefreshDatabase` test runs against the **local MySQL
database** and fails with `SQLSTATE[42S02]: Base table or view not found … Unknown table`
(the local DB isn't migrated with the test schema, and `migrate:fresh` tries to drop tables
that exist only in CI). All 37 failures (Auth, Settings, Share, Goal, Site, Dashboard, …)
trace to this single cause — none are real code bugs.

**Why it matters:** developers cannot trust `php artisan test` locally, `composer ci:check`
can never pass on a dev machine, and the project's "107/107 green" history is not
reproducible. It also silently masks real regressions.

**Fix options (pick one, do it):**
1. **Best:** introduce a `.env.testing` (SQLite `:memory:`) and make sure the testing
   environment never falls back to the dev `.env` — e.g. document/verify that `APP_ENV=testing`
   loads `.env.testing`. Laravel's env precedence should be *checked* here, because currently
   the `.env` is winning, which suggests something is force-loading it (see #4 on the guard).
2. Add a `DB_DATABASE` dedicated to tests (e.g. `lumina_test`) and run `migrate` before tests.
3. Short-term guardrail: add a CI-style `composer test:ci` script that runs the suite with
   SQLite explicitly and a pre-commit check that `APP_ENV=testing php artisan test` is green.

**Acceptance criteria:** `php artisan test` passes fully on a clean checkout of a dev machine.

### 2. No data-retention policy — the `events` table grows unbounded

**Problem:** raw events are inserted forever; there is no pruning, archive, or retention
setting anywhere in `app/` or `packages/lumina-core` (only the `Event` model exists — no
`delete`/`prune` logic). `daily_visitor_stats` aggregates exist precisely to make raw-row
deletion possible, but nothing uses them that way.

**Why it matters:** self-hosted deployments have unbounded disk growth; GDPR "right to
erasure" and data-minimization expectations are unmet; dashboard queries slow as the table
grows even with covering indexes.

**Suggested approach:**
- Add a per-site retention setting (days, default e.g. 90) in the `sites` table.
- Add a scheduled command `lumina:prune-events` (see #8) that deletes raw `events` older than
  retention, keeping `daily_visitor_stats` (which is anonymous + aggregate). Run via the
  scheduler, not a cron user has to remember.
- Document the privacy story: raw IPs are already never stored; pruning makes the claim real.

### 3. Runtime dependency on a third-party GeoIP service (`ip-api.com`)

**Problem:** `InsertEvent` does a synchronous `Http::timeout(2)->get("http://ip-api.com/…")`
for country resolution when no trusted-proxy country header is present. That is an external,
rate-limited, unauthenticated third-party call executed inside the queue worker.

**Why it matters:** it can fail silently (already caught), but it introduces a runtime
dependency for a self-hosted product; unencrypted `http://`; no self-hosting story; and it
blocks the queue worker for up to 2s per miss when the cache is cold.

**Suggested approach:** bundle a local GeoIP database (GeoLite2 / `geoip2/geoip2` with a
downloaded mmdb) or make the provider pluggable (config `analytics.geoip.driver`) so
self-hosters can choose local lookup, `ip-api`, or disabled. Also consider queueing the lookup
as a separate step so the main insert never waits.

### 4. `npm audit`: 3 high-severity vulnerabilities

**Problem:** `npm audit` reports 3 high-severity issues in the JS dependency tree
(dev/lint tooling chain). `.planning/codebase/CONCERNS.md` previously recorded 9; still not clean.

**Fix:** run `npm audit` to identify the packages, update them (`npm update` / targeted
`overrides` in `package.json`), then re-run `npm audit` + `npm run build` + `npm run lint:check`.

---

## 🟠 Medium — quality, DX, and maintenance debt

### 5. `composer ci:check` cannot pass locally (compounds #1)

`ci:check` = `npm run lint:check` + `@test` + `phpstan analyse`. Two problems:

- Tests fail locally (see #1), so the gate is un-runnable on dev machines.
- The composer `test` alias and `ci:check` are the documented local gates but the environment
  mismatch makes them aspirational. Fix #1 first, then verify `composer ci:check` is green
  locally **and** in CI with the same commands.

### 6. PHPStan analysis needs a manual `--memory-limit=2G`; package core is not analyzed

- `phpstan.neon` level 7 passes only when invoked with `--memory-limit=2G` (documented in
  `CONCERNS.md`). Put the memory limit in `phpstan.neon` (`parameters.memoryLimit`) so the
  documented `composer ci:check` works without flags.
- **Gap:** analysis scope covers `app/`, `bootstrap/`, `config/`, `database/`, `routes/` but
  **not `packages/lumina-core/src`** — which contains the most security-sensitive code
  (middleware, collect controller, identity hashing). Extend the phpstan paths to include the
  package and fix whatever level-7 findings surface (many may pre-exist).

### 7. Three overlapping dashboard/admin surfaces to maintain

The project maintains **three** UIs for the same domain:

- Standalone Vue 3 + Inertia SPA dashboard (`resources/js/pages/Dashboard.vue` + `AnalyticsDashboard`).
- Livewire embedded dashboard (`packages/lumina-core/src/Livewire/Dashboard.php` + Blade view).
- Filament admin plugin (`packages/lumina-core/src/Filament/*` + `AdminPanelProvider`).

Each has its own feature-parity drift (e.g. custom-events breakdown exists in Vue; the
Livewire/Filament surfaces lag). Every analytics change must now be reflected in three places.

**Suggested approach:** explicitly declare which surface is the canonical product (the Vue SPA
appears to be it) and either (a) deprecate the Livewire + Filament dashboards, or (b) split the
package so host-app surfaces are thin renderers over `AnalyticsService` only — never
re-implementing metric logic. At minimum, add a parity checklist per release.

### 8. No scheduled tasks at all

`routes/console.php` has no `Schedule::` definitions. The two backfill commands
(`BackfillDailyVisitorStats`, `BackfillCleanPath`) are only invoked manually or from the
seeder. Consequences:

- `daily_visitor_stats` can drift if an `InsertEvent` upsert is lost or a backfill is needed
  after a migration — nothing reconciles it.
- Pruning (see #2) and any nightly report/export have nowhere to live.

**Suggested approach:** add a `schedule()` block in `routes/console.php`:
`lumina:backfill-visitor-stats` (nightly, to reconcile), `lumina:prune-events` (hourly/nightly),
and document the required `schedule:run` cron line in the deployment README.

### 9. No frontend test framework

Frontend is validated only by static analysis (`vue-tsc`, `eslint`, `prettier`) — no Vitest,
no component tests, no Playwright e2e. Given the SPA is the flagship dashboard and hosts
client-side identity logic (`tracker.js`), behavior has no automated guardrail.

**Suggested approach:**
- **Vitest + Vue Test Utils** for component behavior (e.g. `SiteSwitcher`, `AnalyticsControlBar`,
  filter state in the URL).
- **Playwright** for one happy-path e2e (login → create site → see dashboard) and a tracker
  smoke test that asserts `script.js` sends identity params to `/api/collect`.
- Wire into `ci:check` so CI enforces it.

### 10. `daily_visitor_stats` indexing/portability follow-ups

The table is keyed `UNIQUE (site_id, date, visitor_hash)` with `INDEX (site_id, date)`. The
bounce-rate query uses a correlated subquery over it — fine, but confirm the covering index
matches the actual query plan on large data. Also verify the new `visitor_id`-based identity
flow doesn't make `daily_visitor_stats` double-count when a visitor appears as both
`visitor_id` (JS) and `visitor_hash` (fallback) — the metrics now `COALESCE(visitor_id,
visitor_hash)`, so the aggregate table (which only stores `visitor_hash`) should be fed the
*resolved* identity consistently. Add a test for a mixed JS/non-JS population.

### 11. Tracker build tooling inconsistency

The documented build is `npm run build:tracker` (`terser`), but the latest tracker bundle was
minified with **esbuild**. Both are fine, but the checked-in `public/js/script.js` should be
reproducible. Standardize on one tool in the script, and make the `TrackerScriptSizeTest`
(< 2 KB gzipped) part of `ci:check` so regressions are caught in CI, not only locally.

### 12. Minor hygiene

- `.env.example` ships `APP_DEBUG=true` — flip to `false` with a comment for production deploys.
- Pint previously flagged missing trailing blank lines in several test files — already partially
  cleaned; run `composer lint` (not just `--test`) as part of the release checklist.
- `README.md` and the homepage were just updated to match the current privacy model — keep them
  in sync with each release (add a "docs updated?" item to the release checklist).

---

## 🟡 Feature opportunities (product roadmap)

Ordered roughly by value for a self-hosted Plausible-class product:

### 13. Webhooks & threshold alerts
Notify owners when traffic spikes or a goal converts (Discord/Slack/email). Natural extension
of the queue architecture; scheduled check (see #8) can fire these without extra infra.

### 14. Timezone-aware analytics
Daily bucketing and `daily_visitor_stats` currently use server timezone. Expose a per-site
`timezone` setting and bucket by the site's TZ — a table-stakes expectation for a global
audience and easy to implement in `dateExpression()`.

### 15. Funnels & path analysis
Users already have custom events + goals + `clean_path`. A simple funnel builder (step paths
→ drop-off rates) is the single most valuable analytics feature missing vs. Plausible.

### 16. Team / multi-user site access
Sites are `owner_id`-only. Shared dashboards exist but no collaborator model. A
`site_user` pivot with roles (owner/editor/viewer) unlocks teams without much schema risk.

### 17. User-facing GDPR data export & site deletion
`DeleteUser` exists for account deletion; add a "download my data" action (export user's
sites + aggregate stats as JSON) to complete the compliance story.

### 18. A/B / campaign comparison
UTM capture already exists — add side-by-side UTM campaign comparison and goal-conversion-per-
campaign views to close the marketing loop.

### 19. Localization (i18n)
The UI is fully English. For a product sold to the Laravel community (global), `vue-i18n` +
Laravel translations is a reasonable v1.2 item.

### 20. API hardening & documentation
An unauthenticated `POST /api/collect` (rate-limited) plus an authenticated `Api/V1/Stats`
read API exist. Document the read API (auth via site `api_token`), add per-token rate limits,
and publish OpenAPI specs. The collector also accepts `X-Country` as a first-party override —
document this is *not* a spoof risk since country is derived data, not identity.

---

## ✅ Already strong (protect these)

- **Privacy model:** opaque client IDs + stable-salt fallback, zero raw IP storage, no cookies,
  no consent banner — recently implemented and documented consistently in README + homepage.
- **Tracking pipeline:** terminable middleware (zero latency), atomic rate limiting, cached site
  lookups, idempotent `event_id` inserts, portable upsert (SQLite/MySQL), trusted-proxy boundary.
- **Monorepo discipline:** shared domain extracted to `packages/lumina-core` with its own test
  suite; host app stays thin.
- **Deployment kit:** Dockerfile, docker-compose, Supervisor worker, nginx config, Laravel Cloud
  path documented.
- **Performance work:** analytics aggregated in SQL with tagged caching; deferred Inertia props
  for breakdown cards; tracker payload ~1.1 KB gzipped (under the 2 KB budget).

---

## Suggested execution order

1. **#1** test-environment fix (unblocks everything; makes CI and local mean the same thing).
2. **#4** `npm audit` clean-up (quick, low-risk).
3. **#6** phpstan memory limit + extend scope to the package (find latent issues early).
4. **#2 + #8** retention + scheduler together (they need each other; add the cron line to docs).
5. **#9** frontend test framework (Vitest first, then one Playwright smoke).
6. **#7** declare the canonical dashboard surface and reduce triplication.
7. **#5** verify `composer ci:check` green locally after the above.
8. Roadmap items (#13–#20) as v1.2 planning inputs.

---

*This document is grounded in the repository as of August 13, 2026. Re-run the checks (tests,
`npm audit`, `composer ci:check`) after implementing each item to confirm improvement.*
