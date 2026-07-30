# Phase 14: Data Export Engine (CSV/JSON) - Research Notes

## Overview
This phase requires implementing streamed CSV and JSON export routes for Pageviews, Custom Events, and Aggregated Analytics. It needs to respect date range filters and site access policies, and provide UI elements in the dashboard for triggering these downloads.

## Technical Findings

### 1. Existing Export Implementation
- **Current State:** There is already a basic CSV export for all events located at `App\Http\Controllers\SiteController@export` (route `sites.export`). It uses `fputcsv` to stream `site->events()` to a CSV file.
- **Frontend Usage:** `resources/js/Pages/Dashboard.vue` currently hardcodes a link to this export: `:href="\`/sites/${activeSite.id}/export\`"`.

### 2. Differentiating Events
Lumina uses a single `events` table for both pageviews and custom events. 
- **Pageviews:** Dispatched from the tracker with a `null` name and metadata. Thus, they have `metadata` set to `null` in the database. Filter: `whereNull('metadata')`.
- **Custom Events:** Dispatched with a `name` and optionally `props` (stored in `metadata`). Filter: `whereNotNull('metadata')` and checking for the presence of the `name` key within the JSON.
- **Aggregated Analytics:** Available via `AnalyticsService@getOverview()`.

### 3. Controller & Route Design
To fulfill all requirements, the existing `export` method needs significant expansion:
- Should likely accept query parameters:
  - `type`: `pageviews`, `events`, `summary` (for Aggregated Analytics)
  - `format`: `csv`, `json`
  - `period`, `start_date`, `end_date`: For date range filtering.
- Re-use the existing date resolution logic found in `DashboardController@resolveDateRange` to ensure exact parity with the dashboard view.
- To keep `SiteController` clean, consider creating a dedicated `ExportController`.

### 4. Streaming Mechanisms
- **CSV Streaming:** The existing `response()->stream()` using `fputcsv` is appropriate. We should chunk the queries (`->chunk(1000)`) to maintain low memory usage.
- **JSON Streaming:** For `events` or `pageviews`, doing a standard `json_encode` on the full collection will exhaust memory on large datasets. Instead, manually stream the JSON by writing `[` to the output buffer, chunking through records and writing them separated by `,`, then closing with `]`.

### 5. Aggregated Analytics Format Considerations
- **JSON Format:** Exporting the `getOverview()` array payload directly as JSON is trivial.
- **CSV Format:** Mapping `getOverview()` (which contains multiple distinct tables like top pages, devices, browsers, and daily series) to a single CSV is non-standard. The plan must decide how to handle this:
  - Option A: Only support JSON for Aggregated Analytics.
  - Option B: Export a ZIP archive containing multiple CSV files for each metric.
  - Option C: Flatten specific summary metrics into a single CSV.

### 6. Frontend UI
- The existing "Export CSV Button" in `Dashboard.vue` needs to be replaced with a modal or dropdown menu.
- The UI should allow the user to select the **Type** (Pageviews, Custom Events, Overview) and **Format** (CSV, JSON).
- The action button must trigger a file download using the current dashboard date context (`period`, `start_date`, `end_date`).

## Actionable Takeaways for Planning
1. **Plan to create an Export Modal/Dropdown** in Vue to capture user intent before triggering the download.
2. **Update the API Endpoint** to handle filtering (type, format, dates) and enforce site scoping correctly.
3. **Decide on Aggregated CSV Structure:** explicitly define how `Aggregated Analytics` should be formatted if exported as CSV, or restrict it to JSON.
4. **Implement JSON Streaming** properly using output buffering to respect server limits.
