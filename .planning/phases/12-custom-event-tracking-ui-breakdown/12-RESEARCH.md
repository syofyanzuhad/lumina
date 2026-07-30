# Phase 12 Research: Custom Event Tracking UI & Breakdown

## Domain Summary

Custom event tracking in Lumina allows website owners to record arbitrary user actions beyond standard pageviews (e.g., `purchase`, `signup`, `button_click`, `form_submit`) along with arbitrary key-value metadata properties (e.g., `{ "plan": "pro", "amount": 29.99 }`).

In the Lumina database architecture:
- Custom events share the unified `events` table with standard pageview events.
- Standard pageviews have `metadata = NULL`.
- Custom events store their event name and associated properties inside the `metadata` JSON column formatted as:
  ```json
  {
    "name": "purchase",
    "props": {
      "plan": "pro",
      "amount": 29.99
    }
  }
  ```
- Prior to Phase 12, custom events were only displayed as a simple top-10 list at the bottom of the overview dashboard without filtering, property breakdown, timeline charts, or individual event log inspection.

Phase 12 expands custom event analytics into a dedicated **Custom Events Tab**, providing:
1. Event-level filtering and search across standalone Vue/Inertia and embedded Livewire UIs.
2. Metadata property breakdowns (inspecting key-value distributions like top plans, amounts, or categories).
3. Custom event frequency timeline breakdowns over selected date ranges.
4. An interactive event inspector for examining raw individual event logs and formatted JSON payloads.
5. Strict UI and feature parity between the Inertia/Vue SPA and the Livewire embedded component.

---

## Existing Custom Event Infrastructure

### Event Model
- **File**: `packages/lumina-core/src/Models/Event.php`
- **Class**: `Lumina\Core\Models\Event`
- **Fillable Attributes**: `site_id`, `path`, `referrer`, `visitor_hash`, `device_type`, `country`, `browser`, `browser_version`, `os`, `os_version`, `country_code`, `country_name`, `metadata`, `created_at`.
- **Casts**:
  - `'device_type' => DeviceType::class`
  - `'metadata' => 'array'`
- **Key Characteristics**: Timestamps do not update (`public const UPDATED_AT = null`). Uses `MassPrunable` retention query (`created_at <= now() - retention_days`). Belongs to `Site`.

### AnalyticsService — Existing Custom Event Methods
- **File**: `packages/lumina-core/src/Services/AnalyticsService.php`
- **Existing Method**:
  ```php
  public function getCustomEvents(Site $site, CarbonInterface $start, CarbonInterface $end, int $limit = 10): Collection
  ```
  - Queries `Event` records for `$site` between `$start` and `$end` where `metadata` is not null.
  - Filters in PHP memory for items with `metadata['name']`.
  - Groups by `metadata['name']`, counts occurrences, and returns `['name' => string, 'count' => int]`.
  - Caches results under key `lumina:analytics:{siteId}:custom_events_{limit}:{start}:{end}` for 60 seconds.
- **Cache Clearing**: `clearCache(Site $site)` currently clears `lumina:analytics:{$site->id}:custom_events_10`.

### Database Schema
- **Migrations**:
  - `2026_07_26_111909_create_events_table.php`: Base `events` table schema (`id`, `site_id`, `path`, `referrer`, `visitor_hash`, `device_type`, `country`, `created_at`).
  - `2026_07_30_000001_add_metadata_to_events_table.php`: Adds `metadata` JSON column (`nullable()`).
  - `2026_07_30_000002_add_indexes_to_events_table.php`: Adds composite index `events_site_id_created_at_index` on `['site_id', 'created_at']`.
  - `2026_07_30_000003_add_detection_columns_to_events_table.php`: Adds `browser`, `browser_version`, `os`, `os_version`, `country_code`, `country_name`.

### Ingest Pipeline
1. **Client Snippet** (`resources/js/tracker.js` / `public/js/script.js`):
   - Exposes global helper: `window.lumina(eventName, props)`
   - Constructs JSON beacon payload:
     ```json
     {
       "domain": "example.com",
       "path": "/checkout",
       "referrer": "https://google.com",
       "screen_width": 1440,
       "name": "purchase",
       "metadata": { "plan": "pro", "amount": 29.99 }
     }
     ```
   - Sends payload via `navigator.sendBeacon` or `fetch` with `keepalive: true` to `/api/collect`.
2. **Collect Controller** (`packages/lumina-core/src/Http/Controllers/CollectController.php`):
   - Validates incoming request parameters.
   - If `name` is present in input, formats `$metadata` array:
     ```php
     $metadata = [
         'name' => $validated['name'],
         'props' => $validated['metadata'] ?? null,
     ];
     ```
   - Dispatches queue job `InsertEvent::dispatch(...)`.
3. **Queue Job** (`packages/lumina-core/src/Jobs/InsertEvent.php`):
   - Resolves UA agent (browser & OS versioning) and IP geolocation (country code & name).
   - Inserts record into `events` table with `metadata` JSON array.

---

## Dashboard UI Patterns

### Vue/Inertia Dashboard
- **File**: `resources/js/pages/Dashboard.vue`
- **Controller**: `app/Http/Controllers/DashboardController.php`
- **Current State**:
  - Single-page view layout driven by `overview` prop returned from `AnalyticsService::getOverview()`.
  - Responsive header bar with site switcher, date period buttons (`7d`, `30d`), auto-refresh toggle, and CSV export.
  - KPI summary cards (Total Pageviews, Unique Visitors).
  - Daily pageviews bar chart with interactive mouse hover state.
  - Breakdown grid cards: Top Pages, Top Referrers, Device Types, Top Browsers, Top OS, Top Locations.
  - Bottom summary card displaying top custom events list.
- **Pattern for Tabs/Views**:
  - Can introduce tab switching state (`activeTab = 'overview' | 'events'`).
  - When in `'events'` tab, render dedicated custom events layout with event filter selector, custom event timeline chart, metadata key-value distribution breakdown, and inspectable event stream log.

### Livewire Dashboard
- **Component Class**: `packages/lumina-core/src/Livewire/Dashboard.php`
- **Blade Template**: `packages/lumina-core/resources/views/livewire/dashboard.blade.php`
- **Test File**: `packages/lumina-core/tests/Feature/LivewireDashboardTest.php`
- **Current State**:
  - Livewire 4 component with reactive properties: `$site`, `$period`, `$startDate`, `$endDate`.
  - Component calls `AnalyticsService::getOverview($this->site, $start, $end)` and passes data array to `lumina::livewire.dashboard`.
  - Identical visual structure to Vue dashboard using Tailwind CSS utility classes.
- **Pattern for Tabs/Views**:
  - Add reactive public properties: `$activeTab = 'overview'`, `$selectedEvent = null`, `$selectedPropertyKey = null`.
  - Action methods: `setTab(string $tab)`, `selectEvent(?string $eventName)`, `selectPropertyKey(?string $key)`.
  - Render dedicated Custom Events tab view in `dashboard.blade.php` matching the Inertia/Vue layout.

---

## Reusable Assets

| Asset / Utility | Type | Location | Purpose |
| :--- | :--- | :--- | :--- |
| `AnalyticsService::cacheKey()` | Method | `AnalyticsService.php` | Generates deterministic cache keys scoped by site ID, metric, and date bounds. |
| `AnalyticsService::clearCache()` | Method | `AnalyticsService.php` | Clears site-specific analytics cache entries. |
| `InsertEvent` | Job | `packages/lumina-core/src/Jobs/InsertEvent.php` | Queued ingestion of events with metadata. |
| `AppearanceTabs.vue` | Component | `resources/js/components/AppearanceTabs.vue` | Theme switcher component pattern. |
| `UI Components` | Component Library | `resources/js/components/ui/` | Card, Badge, Button, Select, Skeleton, Dialog components. |
| `Lucide Icons` | Icons | `@lucide/vue` & Blade SVG | `Tag`, `Filter`, `Calendar`, `Clock`, `ChevronRight`, `Eye`, `Code`. |
| `Date Range Resolver` | Helper | `DashboardController.php` & `Dashboard.php` | Resolves `'7d'`, `'30d'`, or custom date ranges into Carbon bounds. |
| `Inertia Testing` | Test Assertion | `AssertableInertia` | Validates Inertia page props in feature tests. |
| `Livewire Testing` | Test Framework | `Livewire::test()` | Asserts Livewire component state and view output. |

---

## Implementation Recommendations

### AnalyticsService Extensions Needed

The following new methods must be added to `Lumina\Core\Services\AnalyticsService`:

1. **`getCustomEventSummary(Site $site, CarbonInterface $start, CarbonInterface $end, ?string $selectedEvent = null): array`**
   - Returns summary KPIs for custom events: `total_custom_events`, `unique_event_names`, `top_event_name`.

2. **`getCustomEventsList(Site $site, CarbonInterface $start, CarbonInterface $end): Collection`**
   - Returns all distinct custom event names with count and percentage of total custom events.
   - Example item: `['name' => 'purchase', 'count' => 45, 'percentage' => 60.0, 'last_seen' => '2026-07-30 14:20:00']`.

3. **`getCustomEventTimeline(Site $site, CarbonInterface $start, CarbonInterface $end, ?string $eventName = null): Collection`**
   - Returns daily timeseries array of custom event occurrences within the date range.
   - If `$eventName` is provided, filters for that specific custom event; otherwise aggregates all custom events.
   - Structure: `[['date' => '2026-07-30', 'count' => 15], ...]`.

4. **`getCustomEventPropertyKeys(Site $site, string $eventName, CarbonInterface $start, CarbonInterface $end): array`**
   - Extracts all distinct top-level keys inside `metadata['props']` for a chosen event name.
   - Example: For `purchase`, returns `['plan', 'amount', 'currency', 'coupon']`.

5. **`getCustomEventPropertyBreakdown(Site $site, string $eventName, string $propertyKey, CarbonInterface $start, CarbonInterface $end, int $limit = 10): Collection`**
   - Aggregates value distributions for a specific metadata property key.
   - Example for key `plan`: `[['value' => 'pro', 'count' => 30, 'percentage' => 66.7], ['value' => 'enterprise', 'count' => 15, 'percentage' => 33.3]]`.

6. **`getCustomEventLogs(Site $site, CarbonInterface $start, CarbonInterface $end, ?string $eventName = null, int $limit = 50): Collection`**
   - Retrieves individual recent custom event records for the metadata inspector.
   - Returns formatted array with `id`, `created_at`, `path`, `visitor_hash`, `device_type`, `browser`, `os`, `country_name`, `country_code`, `event_name`, `props` (raw JSON & array).

### Vue/Inertia UI Plan

1. **Dashboard Header Tab Bar**:
   - Add tab switcher (`Overview` vs `Custom Events`) in `Dashboard.vue`.
   - Support URL query parameter persistence (e.g., `/dashboard?tab=events&event=purchase`).

2. **Custom Events Tab Component / View**:
   - **Filter & Controls Bar**: Dropdown to select custom event name (or "All Events"), search filter, date range indicators.
   - **KPI Cards**: Total Custom Events, Unique Event Types, Most Frequent Event.
   - **Custom Event Timeline Chart**: Interactive bar chart displaying daily event counts with hover tooltips.
   - **Events Breakdown & Property Inspector Cards**:
     - Left Column: Event Names list with counts and progress bars.
     - Right Column: Property Value Breakdown card with property key selector (e.g. view distribution of `plan`, `category`, etc.).
   - **Event Logs Stream & Metadata Inspector**:
     - Log stream table showing recent occurrences timestamp, path, visitor, device, browser, country, and event name.
     - Expandable metadata inspector drawer/modal or inline JSON viewer showing raw property payload formatted with syntax highlighting or clear key-value badges.

### Livewire UI Plan

1. **State & Properties in `packages/lumina-core/src/Livewire/Dashboard.php`**:
   - `$activeTab = 'overview'` (`'overview'` | `'events'`)
   - `$selectedEvent = null`
   - `$selectedPropertyKey = null`
   - Action methods: `setTab($tab)`, `selectEvent($name)`, `selectPropertyKey($key)`.

2. **Template in `packages/lumina-core/resources/views/livewire/dashboard.blade.php`**:
   - Top tab navigation control (`Overview` vs `Custom Events`).
   - Dedicated Blade partial or section for `@if ($activeTab === 'events')`.
   - Replicate identical layout: Custom event selector, KPI summary cards, timeline bar chart, property breakdown progress bars, and recent event logs with expandable JSON metadata viewer.

---

## Validation Architecture

### Key Test Scenarios

1. **AnalyticsService Unit & Integration Tests** (`packages/lumina-core/tests/Feature/AnalyticsServiceTest.php`):
   - `it aggregates custom event list with counts and percentages`
   - `it generates daily timeline timeseries for custom events (all and event-filtered)`
   - `it extracts distinct metadata property keys for a given event name`
   - `it calculates property value distributions for a specified property key`
   - `it fetches recent custom event log records with formatted metadata payload`
   - `it caches custom event queries and flushes correctly on clearCache()`

2. **Inertia Dashboard Controller Tests** (`tests/Feature/DashboardControllerTest.php`):
   - `test_user_can_view_custom_events_tab_on_inertia_dashboard`
   - `test_user_can_filter_custom_events_by_event_name`
   - `test_inertia_dashboard_returns_custom_event_timeline_and_property_breakdowns`

3. **Livewire Dashboard Component Tests** (`packages/lumina-core/tests/Feature/LivewireDashboardTest.php`):
   - `test_livewire_dashboard_switches_between_overview_and_custom_events_tabs`
   - `test_livewire_dashboard_filters_by_selected_custom_event`
   - `test_livewire_dashboard_renders_custom_event_timeline_and_property_breakdown`
   - `test_livewire_dashboard_inspects_custom_event_metadata_payload`

4. **UI Parity Verification**:
   - Assert that both Inertia/Vue and Livewire components display identical metrics for custom events, timelines, property distributions, and raw logs.

---

## Risks & Considerations

1. **Database JSON Query Compatibility Across DB Engines**:
   - The test suite uses SQLite in-memory, while production may use MySQL or PostgreSQL.
   - Using database-native JSON functions (e.g. `JSON_EXTRACT` or `->` JSON operators) can cause syntax incompatibilities in SQLite vs MySQL 8.
   - *Recommendation*: Use Eloquent's built-in JSON arrow syntax (`metadata->name`, `metadata->props->{key}`) or PHP collection filtering for small-to-medium datasets to ensure seamless SQLite and MySQL compatibility.

2. **Performance of Unindexed JSON Filtering**:
   - The `events` table has an index on `(site_id, created_at)`.
   - Filtering `metadata` JSON attributes in SQL without a virtual column index scans all site events in the date range.
   - *Recommendation*: Scoping queries strictly by `site_id` and `created_at` date range *first* using indexed columns, then applying JSON filters on the narrowed result set. Cache all aggregated results via `AnalyticsService` (60s TTL).

3. **Edge Cases in Metadata Structure**:
   - Events logged with missing or empty `props` (e.g. `{ "name": "click", "props": null }`).
   - Non-array or primitive prop values (strings, numbers, booleans vs nested objects).
   - *Recommendation*: Safely handle null/primitive values with fallback guards (`is_array()`, `is_scalar()`, `json_encode()` stringification) to prevent frontend rendering errors.
