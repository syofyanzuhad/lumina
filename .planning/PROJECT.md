# Lumina

## What This Is

Lumina is a lightweight, self-hosted web analytics tool built on Laravel — the first of its kind in the Laravel ecosystem. It gives developers full ownership of their analytics data with a privacy-first, cookie-free tracking approach, targeting small-to-medium scale sites (blogs, small SaaS, agency client sites). It supports both an embedded Livewire dashboard component in existing Laravel apps and a standalone Inertia.js + Vue 3 application.

## Core Value

Developers on Laravel stacks get Plausible-class analytics without leaving the Laravel ecosystem or adding new runtimes.

## Business Context

- **Customer**: Self-hosting developers whose primary stack is already Laravel
- **Revenue model**: Open source / self-hosted (no SaaS billing in v1)
- **Success metric**: Events flowing from a real production site into the dashboard with accurate pageview counts

## Requirements

### Validated

- ✓ Auth system (login, registration, Fortify) — v1.0
- ✓ Vue 3 + Inertia.js + Tailwind CSS v4 scaffold — v1.0
- ✓ PostgreSQL & MySQL multi-site database schema — v1.0
- ✓ Package-core extraction (`packages/lumina-core` path repo) — v1.0
- ✓ Server-side middleware tracking (Path A) & custom metadata — v1.0
- ✓ Vanilla JS tracking script (< 2KB) & `POST /api/collect` ingest (Path B) — v1.0
- ✓ Queue worker & Docker deployment configuration — v1.0
- ✓ Cached aggregation engine (60s TTL) in `lumina-core` — v1.0
- ✓ Embedded Livewire dashboard component & view — v1.0
- ✓ Standalone Inertia + Vue 3 dashboard (KPI cards, charts, site switcher, date filters) — v1.0
- ✓ End-to-end verification test suite (107/107 tests passing) — v1.0

### Active (Milestone v1.1)

- [ ] Enhanced Data Detection — parse User-Agent for detailed Browser & OS versions, IP Geolocation / country code detection
- [ ] Custom Event Tracking UI — surface custom event counts, property breakdowns, and timeline in Vue & Livewire dashboards
- [ ] Goal & Conversion Tracking — define target URL paths or custom events as goals and compute conversion rates
- [ ] Data Export — export pageview and event data to CSV and JSON formats for filtered date ranges
- [ ] Public / Shareable Dashboard — generated read-only tokenized dashboard links for client/team sharing

### Out of Scope

- Real-time live dashboard (WebSocket/Reverb) — polling/manual refresh remains sufficient
- Multi-tenant SaaS billing — single-owner self-hosted focus
- Teams / `team_id` scoping — deferred to v2.0
- Mobile SDK, session replay, feature flags, A/B testing — outside basic analytics scope
- ClickHouse / columnar storage — only if Postgres aggregations degrade at real scale

## Context

- **Shipped State**: Shipped v1.0 with 10 completed phases, 15 plans, and 107 passing tests.
- **Ecosystem gap**: Fills the gap as the premier native Laravel web analytics package & standalone tool.
- **Architecture**: Core domain logic lives in `packages/lumina-core` path package. Can be embedded via Livewire or deployed standalone with Inertia/Vue.
- **Queue & Docker**: Async event processing via `InsertEvent` queued job. Production Docker container and Supervisor worker configurations supplied.

## Constraints

- **Tech Stack**: Laravel 13, PHP 8.3, Vue 3, Inertia.js v3, Livewire 4, Tailwind CSS v4 — no new runtimes
- **Database**: PostgreSQL primary; MySQL supported via standard Eloquent queries
- **Queue**: Database driver for v1; persistent worker process
- **Tracking script**: Vanilla JS, no dependencies, < 2KB minified+gzipped
- **Privacy**: Visitor hash (`hash(IP + UA + daily_salt)`) strictly stateless and non-reversible

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Inertia monolith + Livewire embedded | Serves both standalone SaaS and embedded package use cases without version drift | ✓ Good |
| Package-core as Composer path repository | Enables code sharing between embedded and standalone modes without monorepo build overhead | ✓ Good |
| Queue-based event insert (`InsertEvent`) | Keeps `/api/collect` fast (<200ms) regardless of DB write latency | ✓ Good |
| Daily hash for visitor uniqueness | Stateless, no client storage/cookies, GDPR-safe | ✓ Good |
| PostgreSQL + MySQL compatibility | Enforces standard Eloquent query builder across both database engines | ✓ Good |
| Rate limit per IP + per site | Stricter than IP-only; prevents single site queue flooding | ✓ Good |
| Aggregate on-read with cache (60s TTL) | High performance without complex materialized view maintenance | ✓ Good |

## Evolution

This document evolves at phase transitions and milestone boundaries.

---
*Last updated: 2026-07-30 after v1.0 milestone completion*

