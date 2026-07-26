# Lumina

## What This Is

Lumina is a lightweight, self-hosted web analytics tool built on Laravel — the first of its kind in the Laravel ecosystem. It gives developers full ownership of their analytics data with a privacy-first, cookie-free tracking approach, targeting small-to-medium scale sites (blogs, small SaaS, agency client sites). The dashboard is a fullstack Inertia.js monolith served to authenticated users only.

## Core Value

Developers on Laravel stacks get Plausible-class analytics without leaving the Laravel ecosystem or adding new runtimes.

## Business Context

- **Customer**: Self-hosting developers whose primary stack is already Laravel
- **Revenue model**: Open source / self-hosted (no SaaS billing in v1)
- **Success metric**: Events flowing from a real production site into the dashboard with accurate pageview counts

## Requirements

### Validated

- ✓ Auth system (login, registration, Fortify) — existing
- ✓ Vue 3 + Inertia.js + Tailwind CSS v4 scaffold — existing
- ✓ PostgreSQL-ready Eloquent setup — existing

### Active

- [ ] Site registration — user can register a site and receive a tracking snippet
- [ ] Tracking script — vanilla JS < 2KB, async, no cookies, sends page URL / referrer / screen width / timestamp
- [ ] Ingest endpoint — `POST /api/collect`, rate-limited (per IP + per site), queued insert
- [ ] Privacy-safe visitor uniqueness — daily hash of IP + UserAgent + daily salt (stateless, no localStorage)
- [ ] Dashboard — pageviews, unique visitors, top pages, top referrers, daily chart (last 30 days)
- [ ] Date filter — 7d / 30d / custom range
- [ ] Multi-site — list of sites under one account, ability to switch between them
- [ ] Database compatibility — PostgreSQL and MySQL (Eloquent-compatible queries only)

### Out of Scope

- Real-time live dashboard (WebSocket/Reverb) — v1 polling/manual refresh is sufficient; websockets add complexity before scale is proven
- Multi-tenant SaaS billing — v1 is single-owner self-hosted
- Teams / `team_id` scoping — no validated need; `owner_id` per user; refactor later if proven necessary
- Mobile SDK, session replay, feature flags, A/B testing — outside basic analytics scope
- ClickHouse / columnar storage — only if Postgres is a proven bottleneck under real load
- Custom event tracking dashboard UI — not MVP; build after core analytics are verified in production
- Data export — post-MVP
- Public / shareable dashboard link — post-MVP
- Goal / conversion tracking — post-MVP

## Context

- **Ecosystem gap**: No standalone Laravel analytics product exists — only tracking packages without dashboards. This fills that niche.
- **Scale expectations**: PHP-FPM request-response model is not natural for high-volume event ingest at millions of events/day. Mitigation: queue-based insert (`InsertEvent` job), not synchronous. p95 < 200ms on `/api/collect` is the v1 baseline.
- **Postgres serverless risk**: On Laravel Cloud (Neon), Postgres hibernates when idle. First event after idle may be slow. Must be verified in production end-to-end test, not assumed safe.
- **Visitor hashing**: Daily hash = `hash(IP + UserAgent + daily_salt)`. Salt rotates daily. Not reversible to an individual. Chosen over localStorage session ID for full statelesness — matches Plausible/Umami approach.
- **Existing codebase**: Starter kit scaffold already in place (Vue 3, Inertia 3, Fortify auth, Tailwind v4, Reka UI, TypeScript, Pint, Pest). Work builds on top of this foundation.
- **Deployment target**: Laravel Cloud exclusively. Queue worker runs as a persistent process (not serverless invocation) — must be verified in production load test.

## Constraints

- **Tech Stack**: Laravel 13, PHP 8.3, Vue 3, Inertia.js v3, TypeScript, Tailwind CSS v4, Reka UI — no new runtimes (Node daemons, Elixir, Python)
- **Database**: PostgreSQL primary; MySQL must be supported via standard Eloquent queries (no Postgres-specific raw SQL in migrations or query builders)
- **Queue**: Database driver for v1; upgrading to Redis/Valkey on Laravel Cloud is a one-click change — defer until proven necessary
- **Tracking script**: Vanilla JS, no dependencies, < 2KB minified+gzipped
- **Auth**: Fortify (already installed) — no custom auth logic
- **Teams**: Off — `owner_id` model only in v1
- **Scale**: Designed for small-medium traffic; v1 does not need to handle millions of events/day
- **Privacy**: Visitor hash must be truly non-reversible to an individual — no raw IPs stored

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Inertia monolith (not separate BE-FE) | Splitting BE-FE throws away Inertia, adds CORS/dual deploy/version drift with no MVP benefit | — Pending |
| Queue-based event insert | Keeps `/api/collect` fast regardless of DB write latency; decouples ingest from storage | — Pending |
| Daily hash for visitor uniqueness | Stateless, no client storage, GDPR-safe; matches Plausible approach | — Pending |
| PostgreSQL + MySQL compatibility | Broader hosting reach (Indonesian shared hosting uses MySQL); Eloquent standard queries enforce this | — Pending |
| Rate limit per IP + per site | Stricter than IP-only; prevents a single site from flooding the shared queue | — Pending |
| Database driver for queue (v1) | No concrete need for Redis yet; Laravel Cloud makes upgrade trivial later | — Pending |
| Aggregate on-read with cache (60s TTL) | Avoids premature materialized views; revisit if query times degrade at real load | — Pending |
| Postgres partitioning deferred | Not needed at MVP scale; defer until events table proves to be a bottleneck | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-07-26 after initialization from project-en.md*
