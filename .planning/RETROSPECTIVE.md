# Project Retrospective: Lumina

## Milestone: v1.0 — v1 MVP Complete

**Shipped:** 2026-07-30
**Phases:** 10 | **Plans:** 15

### What Was Built
- **Package-Core Architecture**: Extracted shared models, migrations, `AnalyticsService`, and Livewire components into `packages/lumina-core` path package.
- **Database & Visitor Privacy**: Dual Postgres + MySQL schema with rotating daily salt visitor hashing (`hash(IP + UA + daily_salt)`).
- **Dual Tracking Engine**: Server-side middleware tracking (Path A) and vanilla JS tracker (< 2KB) with public `POST /api/collect` ingest (Path B).
- **Decoupled Queue & Deployment**: Async `InsertEvent` queued job with Dockerfile, docker-compose, Supervisor worker config, and Nginx setup.
- **Dual Dashboards**: Embedded Livewire dashboard component and Standalone Vue 3 + Inertia SPA dashboard with KPI cards, SVG charts, date filters, and site switcher.
- **End-to-End Verification**: Comprehensive test suite with 107/107 passing tests covering feature workflows, size budgets, and scale baselines.

### What Worked
- **Composer Path Repository**: Splitting core domain logic into `packages/lumina-core` allowed zero-overhead development within the monorepo while guaranteeing modularity.
- **Stateless Visitor Hashing**: Rotating daily salt eliminated privacy concerns and GDPR consent banners without client storage complexity.
- **Parallel Phase Execution**: Clear boundary definitions enabled rapid implementation across foundational database schema, script optimization, and UI layers.

### What Was Inefficient
- Initial phase structure required reorganization from a flat 8-phase list to a gated 10-phase model (Phase A package-core vs Phase B standalone) to cleanly align with project requirements.

### Key Lessons
- Design shared domain code as a package early to prevent tight coupling to host application routes and controllers.
- Test both Postgres and MySQL compatibility early in the migration pipeline when targeting multi-database deployment.

---

## Cross-Milestone Trends

| Milestone | Shipped Date | Phases | Plans | Total Tests |
|-----------|--------------|--------|-------|-------------|
| v1.0 | 2026-07-30 | 10 | 15 | 107 |
