# Phase 7: Aggregation Queries & Caching — Research

**Gathered:** 2026-07-30
**Phase:** 07 — Aggregation Queries & Caching
**Status:** Completed

---

## 1. Query Specifications & Methods

`AnalyticsService` provides standard analytical metrics for a given `Site` over a specified date range (`$startDate` to `$endDate`):

1. **Total Pageviews (`DASH-01`)**:
   ```php
   Event::where('site_id', $site->id)
       ->whereBetween('created_at', [$startDate, $endDate])
       ->count();
   ```

2. **Unique Visitors (`DASH-02`)**:
   ```php
   Event::where('site_id', $site->id)
       ->whereBetween('created_at', [$startDate, $endDate])
       ->distinct('visitor_hash')
       ->count('visitor_hash');
   ```

3. **Top Pages (`DASH-03`)**:
   ```php
   Event::where('site_id', $site->id)
       ->whereBetween('created_at', [$startDate, $endDate])
       ->select('path', DB::raw('count(*) as pageviews'))
       ->groupBy('path')
       ->orderByDesc('pageviews')
       ->limit($limit)
       ->get();
   ```

4. **Top Referrers (`DASH-04`)**:
   ```php
   Event::where('site_id', $site->id)
       ->whereBetween('created_at', [$startDate, $endDate])
       ->whereNotNull('referrer')
       ->where('referrer', '!=', '')
       ->select('referrer', DB::raw('count(*) as count'))
       ->groupBy('referrer')
       ->orderByDesc('count')
       ->limit($limit)
       ->get();
   ```

5. **Daily Chart Data (`DASH-05`, `DATE-01..03`)**:
   ```php
   // Grouping events by date in a cross-database compatible manner:
   // Select created_at, group by DATE(created_at) using DB::raw
   // For SQLite / MySQL / Postgres cross-DB compatibility in Eloquent:
   // Eloquent date selection formatted as 'Y-m-d'
   ```

6. **Custom Events Aggregation (`metadata`)**:
   ```php
   Event::where('site_id', $site->id)
       ->whereBetween('created_at', [$startDate, $endDate])
       ->whereNotNull('metadata')
       ->get()
       ->filter(fn ($e) => isset($e->metadata['name']))
       ->groupBy(fn ($e) => $e->metadata['name'])
       ->map(fn ($group, $name) => ['name' => $name, 'count' => $group->count()])
       ->values();
   ```

7. **Caching Strategy (`DASH-07`)**:
   - TTL: 60 seconds (`now()->addSeconds(60)`).
   - Cache key format: `lumina:analytics:{site_id}:{metric}:{start_date}:{end_date}`.

---

## 2. Requirements & Verification Mapping

| Requirement | Description | Implementation Strategy |
|---|---|---|
| **DASH-01** | Total pageviews metric | `getPageviews()` query in `AnalyticsService`. |
| **DASH-02** | Unique visitors metric | `getUniqueVisitors()` distinct query in `AnalyticsService`. |
| **DASH-03** | Top pages list | `getTopPages()` grouped query. |
| **DASH-04** | Top referrers list | `getTopReferrers()` grouped query. |
| **DASH-05** | Daily pageview chart series | `getDailyPageviews()` returning daily date-value array. |
| **DASH-06** | Query accuracy verification | `AnalyticsServiceTest` comparing counts against known manual SQL. |
| **DASH-07** | Cache with TTL ≤ 60 seconds | `Cache::remember()` wrapping each service method. |
| **DATE-01..03** | Date range support (7d, 30d, custom) | Flexible `Carbon` start/end parameters in service methods. |
| **DATA-03** | Postgres + MySQL compatibility | Standard Eloquent without DB-specific functions. |
