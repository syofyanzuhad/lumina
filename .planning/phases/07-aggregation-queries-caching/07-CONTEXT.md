# Phase 7: Aggregation Queries & Caching - Context

**Gathered:** 2026-07-30
**Status:** Active

<domain>
## Phase Boundary

Phase 7 builds the query and caching layer for Lumina's analytics metrics inside `packages/lumina-core`:
1. **`AnalyticsService` (`packages/lumina-core/src/Services/AnalyticsService.php`)**:
   - Accepts `Site $site` and date range (`CarbonInterface $startDate`, `CarbonInterface $endDate` or preset `7d`, `30d`, `custom`).
   - `getPageviews(Site $site, $start, $end): int` — total pageview count.
   - `getUniqueVisitors(Site $site, $start, $end): int` — unique visitor count (`COUNT(DISTINCT visitor_hash)`).
   - `getTopPages(Site $site, $start, $end, int $limit = 10): Collection` — top page paths with count and percentage.
   - `getTopReferrers(Site $site, $start, $end, int $limit = 10): Collection` — top referrers with count and percentage.
   - `getDailyPageviews(Site $site, $start, $end): Collection` — daily breakdown formatted for chart visualization.
   - `getCustomEvents(Site $site, $start, $end, int $limit = 10): Collection` — aggregated custom event names & counts from `metadata->name`.
2. **Database Compatibility**:
   - Uses standard Eloquent / Query Builder.
   - No database-specific raw SQL (compatible with Postgres and MySQL).
3. **Caching Layer**:
   - Wraps metrics using `Cache::remember()` with a 60-second TTL.
   - Cache keys scoped by `site_id`, `start_date`, `end_date`, and metric name.
4. **Pest Test Suite**:
   - `packages/lumina-core/tests/Feature/AnalyticsServiceTest.php` testing metric calculations against seeded database fixtures.

</domain>

<decisions>
- **D-01 (Service Placement):** `AnalyticsService` resides in `packages/lumina-core/src/Services/AnalyticsService.php` so both embedded (Livewire/Filament) and standalone (Inertia/Vue) shells share a single query engine.
- **D-02 (Caching TTL):** 60-second TTL via `Cache::remember()`, keyed deterministically per site and date range.
- **D-03 (Cross-DB Date Grouping):** Use standard Eloquent `selectRaw` formatted date grouping compatible with Postgres & MySQL (or PHP collection grouping over cached date ranges if small dataset).
</decisions>

<canonical_refs>
- `project-en.md` §3.3 — Analytics aggregation queries & caching rules.
- `.planning/REQUIREMENTS.md` — DASH-01 through DASH-07, DATE-01 through DATE-03, DATA-03.
</canonical_refs>
