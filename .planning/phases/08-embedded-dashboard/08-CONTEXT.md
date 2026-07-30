# Phase 8: Embedded Dashboard (Livewire/Filament) - Context

**Gathered:** 2026-07-30
**Status:** Active

<domain>
## Phase Boundary

Phase 8 creates the embedded dashboard presentation layer inside `packages/lumina-core`:
1. **Livewire Component (`packages/lumina-core/src/Livewire/Dashboard.php`)**:
   - Accepts a `Site $site` prop or site ID.
   - Date range state: default `30d` (or `7d`, `30d`, `custom` with `$startDate` and `$endDate`).
   - Interacts with `AnalyticsService` (Phase 7) to fetch overview metrics.
   - Re-renders reactively on date filter change.
2. **Blade Views (`packages/lumina-core/resources/views/livewire/dashboard.blade.php`)**:
   - Styled using Tailwind CSS classes.
   - Renders KPI cards (Total Pageviews, Unique Visitors).
   - Renders Top Pages table (path, pageviews count, percentage bar).
   - Renders Top Referrers table (referrer domain, visit count, percentage bar).
   - Renders SVG/HTML bar chart or lightweight chart for daily pageviews.
   - Renders Custom Events breakdown table (if custom events exist).
   - Shows empty state when site has 0 events with snippet installation instructions.
3. **Service Provider Registration**:
   - Register Livewire component `lumina::dashboard` in `LuminaCoreServiceProvider`.
   - Publish Blade views under `lumina-core-views` tag.
4. **Pest Feature Tests**:
   - `packages/lumina-core/tests/Feature/LivewireDashboardTest.php` testing component mounting, data passing from `AnalyticsService`, date filter updating, and empty state rendering.

</domain>

<decisions>
- **D-01 (Component Tech):** Standard Livewire v3 component (`Lumina\Core\Livewire\Dashboard`) inside `packages/lumina-core`.
- **D-02 (Zero Extra Dependencies for Chart):** Use lightweight SVG/Tailwind CSS bar chart in Blade view so host applications don't need external JS charting packages.
- **D-03 (View Namespace):** Views registered under `lumina` namespace (`lumina::livewire.dashboard`).
</decisions>

<canonical_refs>
- `project-en.md` §3.3 & §4 — Embedded dashboard Livewire component requirements.
- `.planning/REQUIREMENTS.md` — DASH-01 through DASH-07, DATE-01 through DATE-03.
</canonical_refs>
