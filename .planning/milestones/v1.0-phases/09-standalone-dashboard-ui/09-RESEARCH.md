# Phase 9: Standalone Dashboard UI (Inertia/Vue) — Research

**Gathered:** 2026-07-30
**Phase:** 09 — Standalone Dashboard UI (Inertia/Vue)
**Status:** Completed

---

## 1. Controller & Inertia Data Contract

### `DashboardController` (`app/Http/Controllers/DashboardController.php`):
- `GET /dashboard`:
  - Query parameters: `site_id` (optional), `period` (`7d`, `30d`, `custom`), `start_date`, `end_date`.
  - Fetch sites: `$sites = Site::where('owner_id', $user->id)->get(['id', 'domain']);`
  - If `$sites->isEmpty()`, redirect to `sites.create` or render empty state.
  - Active site resolution:
    - If `site_id` passed in request and owned by user: update session `session(['active_site_id' => $siteId])`.
    - Else if session `active_site_id` exists and owned by user: use that.
    - Else fallback to `$sites->first()`.
  - Calculate start/end Carbon dates based on `period`.
  - Retrieve `$overview = $analytics->getOverview($activeSite, $start, $end);`.
  - Return `Inertia::render('Dashboard', [ 'sites' => $sites, 'activeSite' => $activeSite, 'period' => $period, 'overview' => $overview ])`.

---

## 2. Component Structure (`resources/js/Pages/Dashboard.vue`)

- **Header / Toolbar**:
  - Site Switcher select element: `<select v-model="selectedSiteId" @change="switchSite">`.
  - Period Filter buttons: `7d`, `30d`.
- **Summary Section**:
  - KPI Cards: Total Pageviews, Unique Visitors with vibrant badges.
- **Visuals Section**:
  - Daily Pageviews Bar Chart (styled with CSS/SVG bars and Vue tooltips).
- **Details Grid**:
  - Top Pages Table with percentage progress bars.
  - Top Referrers Table with percentage progress bars.
  - Custom Events Table.
- **Empty State**:
  - Rendered when `total_pageviews === 0`, includes link to installation instructions and code snippet.

---

## 3. Requirements Mapping

| Requirement | Description | Implementation Strategy |
|---|---|---|
| **DASH-01** | Total pageviews metric | Displayed in Total Pageviews KPI Card. |
| **DASH-02** | Unique visitors metric | Displayed in Unique Visitors KPI Card. |
| **DASH-03** | Top pages table | Displayed in Top Pages card component. |
| **DASH-04** | Top referrers table | Displayed in Top Referrers card component. |
| **DASH-05** | Daily pageviews chart | Displayed in Vue daily bar chart component. |
| **DATE-01..03** | Date range selection | Reactive period selector triggering Inertia router reload with query params. |
| **SITE-04** | Active site switcher | Dropdown menu switching active site in session & updating dashboard. |
