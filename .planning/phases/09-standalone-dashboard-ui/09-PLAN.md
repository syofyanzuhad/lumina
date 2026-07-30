---
wave: 1
depends_on: []
files_modified:
  - app/Http/Controllers/DashboardController.php
  - routes/web.php
  - resources/js/Pages/Dashboard.vue
  - tests/Feature/DashboardControllerTest.php
autonomous: true
---

# Phase 9 Plan: Standalone Dashboard UI (Inertia/Vue)

<threat_model>
- Unauthorized metric disclosure: `DashboardController` enforces user ownership (`site->owner_id === auth()->id()`).
- Data leaks across user accounts: Site lookup strictly scoped to `auth()->user()->sites()`.
- Invalid period injection: Controller validates period against allowed enum list (`7d`, `30d`, `custom`).
</threat_model>

<tasks>

<task id="01-dashboard-controller-and-routing" autonomous="true">
  <action>Create DashboardController and update dashboard route</action>
  <description>Create `app/Http/Controllers/DashboardController.php`. Resolve active site from request `site_id` or session, default to user's first site. Call `AnalyticsService` to compute overview metrics. Pass `sites`, `activeSite`, `period`, and `overview` to `Inertia::render('Dashboard', ...)`. Update `routes/web.php` binding `GET /dashboard` to `DashboardController@index` under `auth` and `verified` middleware.</description>
  <read_first>routes/web.php</read_first>
  <requirements>DASH-01..07, DATE-01..03, SITE-04</requirements>
  <acceptance_criteria>`DashboardController` handles site switching and period filtering, passing structured props to Inertia.</acceptance_criteria>
</task>

<task id="02-vue-dashboard-page" autonomous="true">
  <action>Build premium Vue 3 Dashboard page component</action>
  <description>Update `resources/js/Pages/Dashboard.vue`. Implement active site switcher select dropdown, date period controls (`7d`, `30d`), KPI summary cards (Total Pageviews, Unique Visitors), interactive daily pageview bar chart, top pages table with percentage bars, top referrers table, custom events list, and empty state with snippet instructions.</description>
  <read_first>resources/js/Pages/Sites/Show.vue</read_first>
  <requirements>DASH-01..05, DATE-01..03, SITE-04</requirements>
  <acceptance_criteria>`Dashboard.vue` renders all metrics dynamically and handles site selection / date range updates cleanly.</acceptance_criteria>
</task>

<task id="03-dashboard-controller-tests" autonomous="true">
  <action>Create Pest feature tests for DashboardController</action>
  <description>Create `tests/Feature/DashboardControllerTest.php`. Assert `GET /dashboard` renders for authenticated users, site switcher updates active site in session, period filter alters date range, unauthorized site access is blocked, and zero-event sites render empty state.</description>
  <read_first>app/Http/Controllers/DashboardController.php</read_first>
  <requirements>DASH-01..07, DATE-01..03, SITE-04</requirements>
  <acceptance_criteria>Pest feature tests pass completely (`php artisan test --filter=DashboardControllerTest`).</acceptance_criteria>
</task>

</tasks>

## Artifacts this phase produces
- Controller: `app/Http/Controllers/DashboardController.php`
- Route Update: `routes/web.php`
- Vue Page: `resources/js/Pages/Dashboard.vue`
- Feature Test: `tests/Feature/DashboardControllerTest.php`

## Verification Criteria
- `php artisan test --filter=DashboardControllerTest` passes.
- `npm run build` compiles Vue assets cleanly without errors.
- Active site switcher changes site and updates metrics.
- Date range buttons reload dashboard with selected period.

## must_haves
- Strict authorization: users can ONLY view metrics for sites they own.
- Rich, state-of-the-art visual styling following Antigravity web design principles.
