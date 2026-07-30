# Phase 12 UI-SPEC: Custom Event Tracking UI & Breakdown

## Overview

This specification establishes the visual, interaction, and structural UI contracts for **Phase 12: Custom Event Tracking UI & Breakdown** in Lumina.

Phase 12 expands Lumina's analytics capabilities by providing a dedicated **Custom Events Tab** across both dashboard surfaces:
1. **Standalone Inertia/Vue SPA** (`resources/js/pages/Dashboard.vue`)
2. **Embedded Livewire Component** (`packages/lumina-core/resources/views/livewire/dashboard.blade.php`)

---

## 1. Design System

Lumina builds upon Tailwind CSS v4 design tokens, maintaining consistency across standalone Inertia/Vue and embedded Livewire dashboard surfaces.

### 1.1 Color Palette & Tokens

| Token / Context | Light Mode Value | Dark Mode Value | Usage |
| :--- | :--- | :--- | :--- |
| **Dominant Surface (60%)** | `bg-card` (`#ffffff`) | `dark:bg-slate-900` (`#0f172a`) | Main dashboard container, card backgrounds |
| **Secondary Surface (30%)** | `bg-muted` (`#f1f5f9`) | `dark:bg-slate-800` (`#1e293b`) | Progress bar track, hover states, code blocks |
| **Borders** | `border-sidebar-border/70` | `dark:border-sidebar-border` / `dark:border-slate-800` | Card borders, table dividers |
| **Primary Text** | `text-foreground` (`#0f172a`) | `dark:text-slate-100` (`#f8fafc`) | Card titles, metric values, primary labels |
| **Muted Text** | `text-muted-foreground` (`#64748b`) | `dark:text-slate-400` (`#94a3b8`) | Secondary descriptions, timestamps, subtitles |
| **Accent Primary (10%)** | `bg-indigo-600` / `text-indigo-600` | `dark:bg-indigo-500` / `dark:text-indigo-400` | Active tab pill, active event badges, primary buttons |
| **Accent Success** | `bg-emerald-500` / `text-emerald-600` | `dark:text-emerald-400` | Live indicator, positive status |
| **Accent Warning** | `bg-amber-500` / `text-amber-500` | `dark:text-amber-400` | Warning badges, caution indicators |
| **Accent Secondary Chart** | `bg-sky-600` / `text-sky-600` | `dark:bg-sky-500` | Property value distribution progress bars |

### 1.2 Spacing & Layout Tokens

- **8-Point Spacing Scale**: `4px` (`p-1`), `8px` (`p-2`), `12px` (`p-3`), `16px` (`p-4`), `24px` (`p-6`), `32px` (`p-8`), `48px` (`p-12`).
- **Grid Gaps**: `gap-4` (16px) for KPI cards; `gap-6` (24px) for breakdown section grids.
- **Border Radius**:
  - Main Cards: `rounded-xl` (12px)
  - Control Pills & Buttons: `rounded-lg` (8px)
  - Select Drops & Badges: `rounded-md` (6px)
  - Progress Bars & Indicators: `rounded-full` (9999px)
- **Shadow Scale**:
  - Base Cards: `shadow-sm`
  - Active Tab / Hovered Bar: `shadow-md shadow-indigo-500/20`
  - Floating Tooltips & Modals: `shadow-lg`

### 1.3 Surface-Specific UI Token Contracts

#### Custom Events Tab Control (Header Switcher)
- **Container**: `flex items-center gap-1.5 p-1 bg-muted rounded-xl border border-sidebar-border/50`
- **Active Tab Pill**: `bg-indigo-600 text-white shadow-md shadow-indigo-500/20 dark:bg-indigo-500 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all`
- **Inactive Tab Pill**: `bg-transparent text-muted-foreground hover:text-foreground hover:bg-muted/80 px-3 py-1.5 text-xs font-semibold rounded-lg transition-all`

#### Card Containers
- **Inertia/Vue**: `rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm`
- **Livewire/Blade**: `rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm`

#### Event Filter Dropdown
- **Style**: `rounded-md border-0 py-1.5 pl-3 pr-8 text-xs font-semibold ring-1 ring-inset ring-sidebar-border focus:ring-2 focus:ring-indigo-600 dark:bg-slate-900 dark:text-slate-100 bg-card text-foreground`

#### Custom Event Timeline Chart
- **Container Height**: `h-44 pt-6 pb-2`
- **Bar Styles**: `w-full bg-indigo-500 dark:bg-indigo-400 rounded-t-md transition-all duration-200 group-hover:bg-indigo-600 dark:group-hover:bg-indigo-300 min-h-[3px]`
- **Tooltip**: `absolute bottom-full mb-2 hidden group-hover:block z-10 rounded bg-slate-900 dark:bg-slate-100 px-2.5 py-1 text-xs font-mono text-white dark:text-slate-900 shadow-lg whitespace-nowrap`

#### Metadata Inspector Drawer / Panel
- **Container**: `rounded-xl border border-sidebar-border/70 dark:border-sidebar-border bg-card p-6 shadow-sm overflow-hidden`
- **Code Block**: `font-mono bg-muted/60 dark:bg-slate-950 p-4 rounded-lg border border-sidebar-border/50 text-xs text-foreground overflow-x-auto`

---

## 2. Typography

All typography relies on system sans-serif font stack (`font-sans`) for headings/body and standard monospace font stack (`font-mono`) for event names, property keys, numbers, and raw JSON payloads.

| Element | Font Stack | Size | Weight | Color Token | Line Height |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Tab Label** | `font-sans` | `12px` (`text-xs`) | `600` (`font-semibold`) | `text-white` (active) / `text-muted-foreground` | `1.25` |
| **Section Title** | `font-sans` | `14px` (`text-sm`) | `700` (`font-bold`) | `text-foreground` / `dark:text-slate-100` | `1.25` |
| **KPI Metric Number** | `font-sans` | `30px` (`text-3xl`) | `900` (`font-black`) | `text-foreground` / `dark:text-slate-100` | `1.1` |
| **KPI Label** | `font-sans` | `11px` (`text-xs`) | `600` (`font-semibold`) | `text-muted-foreground` (uppercase) | `1.2` |
| **Event Name Badge** | `font-mono` | `12px` (`text-xs`) | `600` (`font-semibold`) | `text-indigo-600 dark:text-indigo-400` | `1.2` |
| **Property Key Label** | `font-mono` | `12px` (`text-xs`) | `600` (`font-semibold`) | `text-foreground` | `1.25` |
| **Property Value / Count** | `font-mono` | `12px` (`text-xs`) | `400` (`font-normal`) | `text-muted-foreground` | `1.25` |
| **Log Timestamp** | `font-mono` | `11px` (`text-[11px]`) | `400` (`font-normal`) | `text-muted-foreground` | `1.2` |
| **Empty State Header** | `font-sans` | `18px` (`text-lg`) | `700` (`font-bold`) | `text-foreground` | `1.3` |
| **Empty State Body** | `font-sans` | `14px` (`text-sm`) | `400` (`font-normal`) | `text-muted-foreground` | `1.5` |

---

## 3. Component Inventory

### 3.1 Vue / Inertia SPA Components

#### 1. `CustomEventsTab.vue`
- **Location**: `resources/js/components/CustomEventsTab.vue` (or integrated in `Dashboard.vue`)
- **Props**:
  - `activeTab`: `'overview' | 'events'`
  - `siteId`: `number`
  - `period`: `string`
- **Emits**: `update:activeTab(tab: string)`
- **Visual States**: Populated, Active/Inactive tab pill states.

#### 2. `CustomEventsBreakdown.vue`
- **Location**: `resources/js/components/CustomEventsBreakdown.vue`
- **Props**:
  - `events`: `Array<{ name: string, count: number, percentage: number }>`
  - `selectedEvent`: `string | null`
- **Emits**: `selectEvent(eventName: string | null)`
- **Visual States**:
  - **Empty**: Displays "No custom events tracked." message.
  - **Loading**: Skeleton loader bars (`animate-pulse`).
  - **Populated**: List of custom event names with frequency count and progress bar. Active item highlighted.

#### 3. `CustomEventsTimeline.vue`
- **Location**: `resources/js/components/CustomEventsTimeline.vue`
- **Props**:
  - `dailyEvents`: `Array<{ date: string, count: number }>`
  - `selectedEventName`: `string | null`
- **Emits**: `hoverDay(day: { date: string, count: number } | null)`
- **Visual States**:
  - **Populated**: Interactive vertical bar chart with hover tooltips displaying daily occurrences.
  - **Empty**: Flat zero-baseline line with muted label.

#### 4. `CustomEventInspector.vue`
- **Location**: `resources/js/components/CustomEventInspector.vue`
- **Props**:
  - `eventName`: `string`
  - `propertyKeys`: `string[]`
  - `selectedPropertyKey`: `string | null`
  - `propertyBreakdown`: `Array<{ value: string, count: number, percentage: number }>`
  - `recentLogs`: `Array<{ id: number, created_at: string, path: string, visitor_hash: string, metadata: object }>`
- **Emits**:
  - `selectPropertyKey(key: string)`
  - `closeInspector()`
- **Visual States**:
  - **Property Selector**: Pill list or select menu of metadata keys (e.g. `plan`, `amount`, `category`).
  - **Distribution List**: Progress bar list of top values for the selected property key.
  - **Log Stream Table**: Clickable rows showing event timestamps, URL path, visitor hash, and expandable JSON viewer.

---

### 3.2 Livewire Component & Blade Partial Inventory

#### Component Class: `Lumina\Core\Livewire\Dashboard`
- **Location**: `packages/lumina-core/src/Livewire/Dashboard.php`
- **Public Properties**:
  - `public string $activeTab = 'overview'` (`'overview'` | `'events'`)
  - `public ?string $selectedEvent = null`
  - `public ?string $selectedPropertyKey = null`
- **Action Methods**:
  - `public function setTab(string $tab): void`
  - `public function selectEvent(?string $eventName): void`
  - `public function selectPropertyKey(?string $key): void`

#### Blade Template: `packages/lumina-core/resources/views/livewire/dashboard.blade.php`
- **Structure**:
  - `@if ($activeTab === 'events')` conditional layout section rendering:
    1. Header controls with site & date range selector + Overview / Custom Events tab switcher.
    2. Summary KPI cards for Custom Events (Total Events, Event Types, Top Event Name).
    3. Custom Event Timeline Bar Chart.
    4. Two-column breakdown grid:
       - Left Column: Custom Event Names breakdown list with selection click handlers (`wire:click="selectEvent('name')"`).
       - Right Column: Property Metadata breakdown card with key selector tabs (`wire:click="selectPropertyKey('key')"`) and value distribution bars.
    5. Recent Custom Events Log Stream with inline `<details>`/`<summary>` JSON inspector payload viewer.

---

## 4. Interaction Design

### 4.1 Tab Navigation
- Clicking **"Custom Events"** tab switches view without full page reload.
- Preserves selected `activeSite` and `period` (`7d`, `30d`).
- Standalone SPA updates browser URL query parameters: `?tab=events`.
- Livewire re-renders the component with `$activeTab = 'events'`.

### 4.2 Event Filter Selector
- Top bar dropdown labeled **"Filter by Event"**:
  - Default Option: `"All Custom Events"` (aggregates all metadata non-null events).
  - List Options: Dynamic list of tracked event names (e.g., `purchase`, `signup`, `click_cta`).
- Selecting an event name updates:
  - Timeline chart to show occurrences of that specific event.
  - Property key selector options to extract top-level metadata keys for that event.
  - Log stream to show recent logs filtered by that event name.

### 4.3 Breakdown Row Click
- Clicking any event row in the **Top Events** card sets `$selectedEvent` to that event name.
- Highlights the selected event row with an active border (`border-indigo-500 bg-indigo-500/5`).
- Automatically resolves and populates the **Metadata Inspector** section below.

### 4.4 Metadata Property Key Inspector
- Horizontal pill tab bar showing available property keys for the selected event (e.g. `[plan]`, `[amount]`, `[currency]`).
- Clicking a property key (e.g., `plan`) fetches/calculates top values for that key and renders value distribution progress bars (e.g. `pro`: 65%, `basic`: 35%).

### 4.5 Log Stream & Raw JSON Payload Inspection
- Recent event occurrences table displaying: Timestamp, Event Name, URL Path, Visitor Hash (truncated to 8 chars), and Action.
- Clicking **"Inspect Metadata"** or clicking the row expands an inline JSON payload box showing formatted, syntax-highlighted JSON props:
  ```json
  {
    "plan": "pro",
    "amount": 29.99,
    "currency": "USD"
  }
  ```

---

## 5. Copywriting Contract

| Key / Element | Text String | Notes / Context |
| :--- | :--- | :--- |
| **Tab Label 1** | `"Overview"` | Primary dashboard tab |
| **Tab Label 2** | `"Custom Events"` | New dedicated custom events tab |
| **Section Title 1** | `"Top Custom Events"` | Breakdown list title |
| **Section Title 2** | `"Event Frequency Over Time"` | Timeline chart title |
| **Section Title 3** | `"Property Value Breakdown"` | Property inspector title |
| **Section Title 4** | `"Recent Custom Event Logs"` | Event log stream title |
| **Filter Label** | `"Filter by event"` | Dropdown placeholder / label |
| **All Events Option** | `"All Custom Events"` | Default dropdown option |
| **Property Select Label** | `"Select metadata key"` | Label for property pill selector |
| **KPI Total Events** | `"Total Custom Events"` | KPI summary card 1 |
| **KPI Event Types** | `"Unique Event Types"` | KPI summary card 2 |
| **KPI Top Event** | `"Most Frequent Event"` | KPI summary card 3 |
| **Empty State Title** | `"No custom events tracked yet"` | Header when no custom events exist |
| **Empty State Subtext** | `"Use window.lumina('event_name', { props }) to start tracking custom actions."` | Help instructions |
| **Loading State** | `"Loading custom events data..."` | Skeleton state text |
| **Error State Title** | `"Failed to load custom events"` | Query error message |
| **Error Action** | `"Click to retry"` | Error recovery button label |
| **Count Format** | `"{N} occurrences"` | Singular/plural count label |
| **Percentage Format** | `"{N} ({P}%)"` | Value count + percentage label |
| **Inspector Action** | `"View Raw Payload"` | Log stream expand button |

---

## 6. Parity Contract

To ensure seamless developer experience across standalone and embedded environments, BOTH dashboard surfaces MUST achieve 100% visual and functional parity for Phase 12 features.

### Surface Parity Matrix

| Feature / Element | Standalone Inertia/Vue SPA | Embedded Livewire Component | Parity Requirement |
| :--- | :--- | :--- | :--- |
| **Tab Switching** | Client-side reactive tab state + Inertia router query | Livewire reactive property `$activeTab` + `wire:click` | Identical tab pill UI & layout |
| **Summary KPIs** | Total Events, Event Types, Top Event cards | Total Events, Event Types, Top Event cards | Exact metric match from `AnalyticsService` |
| **Timeline Chart** | Interactive Vue SVG/HTML bar chart with hover tooltips | Alpine/Blade HTML bar chart with hover tooltips | Same height (`h-44`), bar colors & hover |
| **Top Events List** | Progress bar breakdown card with row click event | Progress bar breakdown card with `wire:click="selectEvent"` | Same color badges, counts, percentages |
| **Metadata Inspector** | Vue tab pills for property keys + progress bars | Blade tab pills for property keys + progress bars | Same property value distributions |
| **Event Stream Logs** | Interactive Vue log table with expandable JSON drawer | Blade table with Alpine `<details>` JSON viewer | Identical JSON syntax box styling |
| **Empty & Error States** | Dedicated Vue empty state illustration & code box | Blade `@else` empty state illustration & code box | Identical copywriting & code snippet |

---

## UI Considerations

### 1. Skeleton Loading States
During data fetching or tab switching, both UIs must render skeleton loading states:
- KPI Cards: Pulsing rectangle for metric number (`h-9 w-24 bg-muted animate-pulse rounded-lg`).
- Timeline Chart: 7–30 pulsing vertical bar outlines (`bg-muted/50 animate-pulse rounded-t-md`).
- Breakdown Lists: 4 skeleton rows with title and progress bar outline.

### 2. Error Handling
If an `AnalyticsService` aggregate query throws an exception or fails:
- Render a inline warning card (`border-rose-500/30 bg-rose-500/10 text-rose-600 dark:text-rose-400 p-4 rounded-xl`).
- Display title `"Failed to load custom events"` and a **"Retry"** button that re-triggers `refreshData()` (Vue) or `$refresh` (Livewire).

### 3. Empty States
When `$site` has 0 custom events in the selected date period:
- Render clean centered card with a code icon (`Code` / `<svg>`).
- Header: `"No custom events tracked yet"`.
- Subtext and code block demonstrating sample tracking call:
  ```js
  window.lumina('purchase', { plan: 'pro', amount: 29.99 });
  ```

### 4. Inspector Expand Transition
- Expandable JSON payload drawer in log stream table must use CSS transition (`transition-all duration-200 ease-in-out`).
- Formatted JSON must use monospace font with soft dark background (`bg-muted/60 dark:bg-slate-950 p-3 rounded-md border border-sidebar-border/50 text-xs font-mono`).

### 5. Responsive Behavior
- **Mobile (< 648px)**: Stack all cards into a single column (`grid-cols-1`). Tab bar wraps horizontally.
- **Tablet (648px – 1024px)**: 2-column layout for KPI cards and breakdown section (`grid-cols-1 md:grid-cols-2`).
- **Desktop (> 1024px)**: 3-column layout for KPI metrics, side-by-side Top Events vs Metadata Property Breakdown, full-width timeline chart and log stream inspector.
