# Roadmap: Lumina

## Milestones

- ✅ **v1.0 MVP** — Phases 1–10 (shipped 2026-07-30)
- 🔄 **v1.1 Feature Expansion** — Phases 11–16 (in progress)

## Phases

<details>
<summary>✅ v1.0 MVP (Phases 1–10) — SHIPPED 2026-07-30</summary>

- [x] Phase 1: Foundation & Database Schema (5/5 plans) — completed 2026-07-30
- [x] Phase 2: Site Management (CRUD) (2/2 plans) — completed 2026-07-30
- [x] Phase 3: Package-Core Extraction (1/1 plan) — completed 2026-07-30
- [x] Phase 4: Middleware Tracking & Metadata Migration (1/1 plan) — completed 2026-07-30
- [x] Phase 5: Tracking Script & Ingest Endpoint (1/1 plan) — completed 2026-07-30
- [x] Phase 6: Queue Worker & Deployment Config (1/1 plan) — completed 2026-07-30
- [x] Phase 7: Aggregation Queries & Caching (1/1 plan) — completed 2026-07-30
- [x] Phase 8: Embedded Dashboard (Livewire) (1/1 plan) — completed 2026-07-30
- [x] Phase 9: Standalone Dashboard UI (Inertia/Vue) (1/1 plan) — completed 2026-07-30
- [x] Phase 10: End-to-End Verification & Production Readiness (1/1 plan) — completed 2026-07-30

</details>

- [x] Phase 11: Enhanced Data Detection (UA Browser/OS & Geolocation) (1/1 plan) — completed 2026-07-30
- Parse User-Agent for detailed Browser & OS versioning
- Geolocation IP detection (country code & country name)
- Aggregate queries for Browsers, Operating Systems, Countries in `AnalyticsService`
- Render Browser, OS, and Location cards in Vue & Livewire dashboards

- [x] Phase 12: Custom Event Tracking UI & Breakdown (1/1 plan) — completed 2026-07-30
- Dedicated Custom Events tab and filters in standalone & Livewire UI
- Event metadata inspector & timeline breakdown
- Parity across Inertia/Vue and Livewire components

### Phase 13: Goal & Conversion Tracking
- Goals schema & migration (`goals` table: path or custom event target)
- Goal management CRUD API & UI in site settings
- Conversion rate calculations in `AnalyticsService`
- Goal conversion performance cards in dashboard

### Phase 14: Data Export Engine (CSV/JSON)
- Streamed CSV and JSON export routes for Pageviews & Custom Events
- Respect date range filters and site scoping
- UI export modal & download actions in dashboard

- [x] Phase 15: Public & Shareable Dashboards (2/2 plans) — completed 2026-07-31
- Site share settings & secret token generator
- Read-only route & controller for `/share/{token}`
- Shareable view mode for Vue & Livewire dashboard layouts

### Phase 16: Milestone v1.1 Verification & E2E
- E2E verification test suite for all v1.1 features
- Zero regression checks across all 107+ tests
- Documentation and README update

## Progress

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Foundation & Schema | v1.0 | 5/5 | Complete | 2026-07-30 |
| 2. Site Management | v1.0 | 2/2 | Complete | 2026-07-30 |
| 3. Package-Core Extraction | v1.0 | 1/1 | Complete | 2026-07-30 |
| 4. Middleware Tracking | v1.0 | 1/1 | Complete | 2026-07-30 |
| 5. Tracking Script | v1.0 | 1/1 | Complete | 2026-07-30 |
| 6. Queue Worker & Docker | v1.0 | 1/1 | Complete | 2026-07-30 |
| 7. Aggregation Queries | v1.0 | 1/1 | Complete | 2026-07-30 |
| 8. Embedded Dashboard | v1.0 | 1/1 | Complete | 2026-07-30 |
| 9. Standalone Dashboard | v1.0 | 1/1 | Complete | 2026-07-30 |
| 10. E2E Verification | v1.0 | 1/1 | Complete | 2026-07-30 |
| 11. Enhanced Data Detection | v1.1 | 1/1 | Complete | 2026-07-30 |
| 12. Custom Event Tracking UI | v1.1 | 1/1 | Complete | 2026-07-30 |
| 13. Goal & Conversion Tracking | v1.1 | 1/1 | Complete | 2026-07-30 |
| 14. Data Export Engine | v1.1 | 1/1 | Complete | 2026-07-30 |
| 15. Public & Shareable Dashboards | v1.1 | 2/2 | Complete | 2026-07-31 |
| 16. Milestone v1.1 E2E Verification | v1.1 | 2/2 | Complete   | 2026-07-30 |

## Backlog (Post-v1.1)

- Postgres table partitioning (V2-01) — only if events table bottlenecks
- ClickHouse migration (V2-02) — only if Postgres aggregations degrade at real scale
- Team support / `team_id` migration (V2-03)

---
*Roadmap updated: 2026-07-30 for Milestone v1.1 launch*
