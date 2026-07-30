---
wave: 1
depends_on: []
files_modified:
  - packages/lumina-core/src/Livewire/Dashboard.php
  - packages/lumina-core/resources/views/livewire/dashboard.blade.php
  - packages/lumina-core/src/LuminaCoreServiceProvider.php
  - packages/lumina-core/tests/Feature/LivewireDashboardTest.php
autonomous: true
---

# Phase 8 Plan: Embedded Dashboard (Livewire/Filament)

<threat_model>
- Unauthorized metric disclosure: Livewire component requires valid `Site` model instance passed by host application.
- UI layout breakage in host app: Tailwind CSS classes used cleanly with scope isolation and no global style overrides.
- Unexpected null state crashes: Empty state template handles zero-event scenarios gracefully.
</threat_model>

<tasks>

<task id="01-livewire-component-and-views" autonomous="true">
  <action>Create Livewire Dashboard component and Blade view</action>
  <description>Create `packages/lumina-core/src/Livewire/Dashboard.php` receiving `Site $site`. Support reactive date periods (`7d`, `30d`, `custom`). Create `packages/lumina-core/resources/views/livewire/dashboard.blade.php` rendering KPI cards, daily SVG bar chart, top pages, top referrers, custom events, and empty state. Register Livewire component `lumina-dashboard` and view namespace in `LuminaCoreServiceProvider.php`.</description>
  <read_first>packages/lumina-core/src/Services/AnalyticsService.php</read_first>
  <requirements>DASH-01, DASH-02, DASH-03, DASH-04, DASH-05, DATE-01, DATE-02, DATE-03</requirements>
  <acceptance_criteria>Livewire component `lumina-dashboard` exists, registers under `lumina` namespace, and renders all metrics.</acceptance_criteria>
</task>

<task id="02-livewire-dashboard-tests" autonomous="true">
  <action>Create Pest feature tests for Livewire Dashboard</action>
  <description>Create `packages/lumina-core/tests/Feature/LivewireDashboardTest.php`. Test mounting the component with a `Site` model, asserting KPI numbers, top pages, referrers, reactive date period switches, and empty state rendering when 0 events exist.</description>
  <read_first>packages/lumina-core/src/Livewire/Dashboard.php</read_first>
  <requirements>DASH-01..07, DATE-01..03</requirements>
  <acceptance_criteria>Livewire feature tests pass completely (`vendor/bin/pest packages/lumina-core/tests/`).</acceptance_criteria>
</task>

</tasks>

## Artifacts this phase produces
- Component: `packages/lumina-core/src/Livewire/Dashboard.php`
- View: `packages/lumina-core/resources/views/livewire/dashboard.blade.php`
- Service Provider Update: `packages/lumina-core/src/LuminaCoreServiceProvider.php`
- Test: `packages/lumina-core/tests/Feature/LivewireDashboardTest.php`

## Verification Criteria
- `vendor/bin/pest packages/lumina-core/tests/` passes completely.
- Livewire component mounts and renders pageviews, unique visitors, top pages, top referrers, and daily chart.
- Reactive date period toggling updates overview numbers.
- `php artisan test --compact` passes cleanly.

## must_haves
- Zero external JS charting libraries required (pure Blade/SVG/Tailwind).
- Component must be registered in `LuminaCoreServiceProvider` for host apps.
