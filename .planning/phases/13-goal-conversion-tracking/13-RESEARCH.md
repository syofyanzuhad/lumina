# Phase 13 Research: Goal & Conversion Tracking

## Overview
This phase implements goal and conversion tracking, allowing users to define specific targets (a URL path or a custom event) and track how effectively they are being met based on the unique visitors during a date range.

## 1. Schema & Models
*   **Location:** `packages/lumina-core`
*   **Migration:** A new migration (e.g., `2026_07_30_000004_create_goals_table.php`) should be created in `packages/lumina-core/database/migrations`.
*   **Table Structure:**
    *   `id` (primary key)
    *   `site_id` (foreign key constrained to `sites`, cascading on delete)
    *   `name` (string, e.g., "Newsletter Signup")
    *   `target_type` (string, either `path` or `custom_event`)
    *   `target_value` (string, e.g., `/success` or `newsletter_subscribed`)
    *   `created_at` & `updated_at` timestamps
*   **Model:** Create `Lumina\Core\Models\Goal` with `Fillable` attributes `['site_id', 'name', 'target_type', 'target_value']`. Add a `goals` relationship to `Lumina\Core\Models\Site`.

## 2. API & Controller
*   **Controller:** Create `App\Http\Controllers\GoalController` to handle goal management (CRUD).
*   **Routes:** Define standard `web` resourceful routes nested under sites, e.g. `POST /sites/{site}/goals`, `PUT /sites/{site}/goals/{goal}`, `DELETE /sites/{site}/goals/{goal}`.
*   **UI Integration:** The site settings are currently housed in `resources/js/Pages/Sites/Show.vue`. We should expand this view to include a "Goals Management" section below the tracking snippet. It should list existing goals and provide a form to create/edit them.

## 3. AnalyticsService Integration
*   **Location:** `packages/lumina-core/src/Services/AnalyticsService.php`
*   **Method:** Add `getGoals(Site $site, CarbonInterface $start, CarbonInterface $end)`:
    *   Fetch all goals for the site.
    *   For each goal, calculate the total `completions` using the `events` table (matching either `path` or `metadata->name` based on `target_type`).
    *   Fetch the total unique visitors for the period via `$this->getUniqueVisitors(...)`.
    *   Calculate `conversion_rate` (completions / unique visitors * 100).
    *   To satisfy the "trend line" requirement, also calculate a daily timeline of completions per goal (similar to how `getDailyPageviews` works) and return it as `trend`.
*   **Cache:** Ensure to add clear logic in `clearCache()` for `lumina:analytics:{site}:goals`.
*   **Overview:** Append the goals array to the array returned by `getOverview()`.

## 4. Dashboard UI Updates
*   **Vue Dashboard:** Update `resources/js/Pages/Dashboard.vue` to render a "Goals" section (likely cards for each goal) using the newly added `goals` key in the overview payload. Display the name, total completions, conversion percentage, and use the trend array to render a sparkline or simple trend line chart.
*   **Livewire Dashboard:** Update `packages/lumina-core/resources/views/livewire/dashboard.blade.php` to ensure parity and display the goal conversion performance cards in the embedded Livewire component as well.
