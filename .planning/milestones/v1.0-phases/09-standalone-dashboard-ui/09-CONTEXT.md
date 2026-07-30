# Phase 9: Standalone Dashboard UI (Inertia/Vue) - Context

**Gathered:** 2026-07-30
**Status:** Active

<domain>
## Phase Boundary

Phase 9 delivers the standalone application presentation shell for Lumina using Inertia.js & Vue 3:
1. **`DashboardController` (`app/Http/Controllers/DashboardController.php`)**:
   - Authenticated route `GET /dashboard`.
   - Fetches user's sites (`Site::where('owner_id', auth()->id())->get()`).
   - Resolves active site from `site_id` query param or session (`active_site_id`), defaulting to user's first site.
   - Accepts `period` query param (`7d`, `30d`, `custom`) with optional `start_date` and `end_date`.
   - Calls `AnalyticsService` to fetch overview metrics.
   - Renders `Inertia::render('Dashboard', [...])`.
2. **Vue 3 Dashboard Page (`resources/js/Pages/Dashboard.vue`)**:
   - Active site switcher select dropdown (`SITE-04`).
   - Period selector buttons (`7d`, `30d`, `custom`).
   - KPI Summary Cards: Total Pageviews & Unique Visitors.
   - Interactive daily pageview bar chart with hover tooltips.
   - Top Pages table with percentage bars.
   - Top Referrers table with percentage bars.
   - Custom Events table.
   - Empty state view when active site has 0 events with snippet reminder.
3. **Pest Feature Tests (`tests/Feature/DashboardControllerTest.php`)**:
   - Test dashboard rendering for user with sites.
   - Test active site switching and date period filtering.
   - Test empty state when user has no events.
   - Test authorization (user cannot view dashboard of a site owned by another user).

</domain>

<decisions>
- **D-01 (Architecture):** Standalone dashboard uses Vue 3 + Inertia.js, consuming the shared `AnalyticsService` from `packages/lumina-core`.
- **D-02 (Site Switcher):** Active site stored in user session and selectable via top navbar / dashboard header dropdown.
- **D-03 (Styling & Aesthetics):** Tailored dark/light mode palette with smooth micro-animations, Inter font, vibrant KPI indicators, and responsive card layouts.
</decisions>

<canonical_refs>
- `project-en.md` §3.3 & §4 — Standalone Vue/Inertia dashboard specification.
- `.planning/REQUIREMENTS.md` — DASH-01..07, DATE-01..03, SITE-04.
</canonical_refs>
