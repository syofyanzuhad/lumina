# Phase 8: Embedded Dashboard (Livewire/Filament) — Research

**Gathered:** 2026-07-30
**Phase:** 08 — Embedded Dashboard (Livewire/Filament)
**Status:** Completed

---

## 1. Livewire Component Architecture

1. **Class**: `Lumina\Core\Livewire\Dashboard`
   - Properties:
     - `public Site $site;`
     - `public string $period = '30d';` // 7d, 30d, custom
     - `public ?string $startDate = null;`
     - `public ?string $endDate = null;`
   - Methods:
     - `setPeriod(string $period): void`
     - `render(AnalyticsService $analytics)`:
       - Computes start and end `Carbon` instances based on `$period`.
       - Retrieves overview payload: `$overview = $analytics->getOverview($this->site, $start, $end);`
       - Returns `view('lumina::livewire.dashboard', $overview)`.

2. **Livewire Component Registration in `LuminaCoreServiceProvider`**:
   - `Livewire::component('lumina-dashboard', \Lumina\Core\Livewire\Dashboard::class);`
   - Register view namespace: `$this->loadViewsFrom(__DIR__.'/../resources/views', 'lumina');`
   - View publishing: `$this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/lumina')], 'lumina-views');`

3. **Blade Template (`packages/lumina-core/resources/views/livewire/dashboard.blade.php`)**:
   - Header with site domain and period filter buttons (`7d`, `30d`, `custom`).
   - Summary cards: Total Pageviews, Unique Visitors.
   - SVG / CSS Bar Chart for daily pageviews.
   - Top Pages table with percentage progress bar.
   - Top Referrers table with percentage progress bar.
   - Custom Events table.
   - Empty state when `total_pageviews === 0`.

4. **Testing Strategy**:
   - Livewire test helpers: `Livewire::test(Dashboard::class, ['site' => $site])`
   - Assert initial render contains metrics.
   - Assert calling `setPeriod('7d')` updates metrics correctly.

---

## 2. Requirements Mapping

| Requirement | Description | Livewire Implementation |
|---|---|---|
| **DASH-01** | Total pageviews metric | Displayed in Summary KPI Card. |
| **DASH-02** | Unique visitors metric | Displayed in Summary KPI Card. |
| **DASH-03** | Top pages table | Displayed in Top Pages table. |
| **DASH-04** | Top referrers table | Displayed in Top Referrers table. |
| **DASH-05** | Daily pageview chart | Rendered via inline SVG/CSS bar chart. |
| **DATE-01..03**| Date range filters (7d, 30d, custom) | Reactive period selector buttons. |
| **DASH-07** | Cache integration | Automatic via `AnalyticsService`. |
