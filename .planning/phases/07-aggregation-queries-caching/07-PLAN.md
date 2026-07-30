---
wave: 1
depends_on: []
files_modified:
  - packages/lumina-core/src/Services/AnalyticsService.php
  - packages/lumina-core/tests/Feature/AnalyticsServiceTest.php
autonomous: true
---

# Phase 7 Plan: Aggregation Queries & Caching

<threat_model>
- Performance bottlenecks on large event tables: Mitigated by 60-second TTL caching (`Cache::remember()`) and efficient index utilization on (`site_id`, `created_at`).
- Cross-tenant data leaks: Mitigated by mandatory `$site->id` scoping on every analytics query.
- SQL syntax incompatibility: Mitigated by strictly using standard Eloquent and Query Builder functions compatible across SQLite, MySQL, and Postgres.
</threat_model>

<tasks>

<task id="01-analytics-service" autonomous="true">
  <action>Create AnalyticsService in packages/lumina-core</action>
  <description>Create `packages/lumina-core/src/Services/AnalyticsService.php`. Implement methods for pageviews, unique visitors (`COUNT(DISTINCT visitor_hash)`), top pages, top referrers, daily pageview chart series, custom events, and a unified `getOverview()` payload. Wrap query results in `Cache::remember()` with a 60-second TTL keyed by site ID and date range.</description>
  <read_first>packages/lumina-core/src/Models/Event.php</read_first>
  <requirements>DASH-01, DASH-02, DASH-03, DASH-04, DASH-05, DASH-07, DATE-01, DATE-02, DATE-03, DATA-03</requirements>
  <acceptance_criteria>`AnalyticsService` class exists with methods returning typed data arrays and collections, utilizing 60-second caching.</acceptance_criteria>
</task>

<task id="02-analytics-service-tests" autonomous="true">
  <action>Create Pest feature tests for AnalyticsService</action>
  <description>Create `packages/lumina-core/tests/Feature/AnalyticsServiceTest.php`. Seed known event fixtures across dates and sites. Verify that pageviews, unique visitors, top pages, referrers, daily pageview series, and custom events return accurate figures matching manual SQL. Verify caching behavior.</description>
  <read_first>packages/lumina-core/src/Services/AnalyticsService.php</read_first>
  <requirements>DASH-06, DASH-07, DATA-03</requirements>
  <acceptance_criteria>Pest feature tests pass completely (`vendor/bin/pest packages/lumina-core/tests/`).</acceptance_criteria>
</task>

</tasks>

## Artifacts this phase produces
- Service: `packages/lumina-core/src/Services/AnalyticsService.php`
- Test: `packages/lumina-core/tests/Feature/AnalyticsServiceTest.php`

## Verification Criteria
- `vendor/bin/pest packages/lumina-core/tests/` passes completely.
- `php artisan test --compact` passes cleanly.
- Pageview, unique visitor, referrer, and daily chart metrics calculate exact numbers.
- Query execution results are cached for 60 seconds.

## must_haves
- All queries MUST be scoped to `$site->id`.
- Queries must use standard Eloquent syntax compatible with both Postgres and MySQL.
- Cache TTL must be 60 seconds or less.
