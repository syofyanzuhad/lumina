---
wave: 02
depends_on: []
files_modified:
  - resources/js/Pages/Sites/Index.vue
  - resources/js/Pages/Sites/Create.vue
  - resources/js/Pages/Sites/Show.vue
autonomous: true
---

# Phase 02 - Gap Fix Plan

> Gap closure plan for Phase 02 Site Management CRUD to align the UI with the Laravel Starter Kit.

## Artifacts this phase produces
- No new symbols are created. Modifies existing site pages to use native UI layout definitions.

## Wave 1

<task>
  <id>task-06-site-ui-fix</id>
  <title>Align Site Pages with Laravel Starter Kit UI Layout</title>
  <description>Update the Index, Create, and Show pages to use `defineOptions` for layouts rather than wrapping everything in an imported `AppLayout` component, and standardize the header styling using the kit's `Heading` component.</description>
  <read_first>
    - resources/js/Pages/Dashboard.vue
    - resources/js/Pages/settings/Profile.vue
    - resources/js/Pages/Sites/Index.vue
    - resources/js/Pages/Sites/Create.vue
    - resources/js/Pages/Sites/Show.vue
  </read_first>
  <action>
    - In `resources/js/Pages/Sites/Index.vue`, `Create.vue`, and `Show.vue`:
      - Remove the `import AppLayout from '@/layouts/AppLayout.vue';` and the `<AppLayout>` wrapper in the template.
      - Add `defineOptions({ layout: { breadcrumbs: [ ... ] } });` inside the `<script setup>` block with the respective breadcrumbs.
      - Change the main wrapping div to use the standard structural layout seen in Dashboard: `<div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">` (or similar standard flex/padding).
      - Replace custom header divs (`<h2 class="text-2xl...">`) with the `<Heading>` component imported from `@/components/Heading.vue`.
      - For the "No sites found" empty state in `Index.vue`, make sure it uses a clean starter kit compatible card or border block (e.g. `border border-sidebar-border/70 rounded-xl`) rather than custom `bg-card/50` if it looks out of place, ensuring it fits the aesthetic of the starter kit.
  </action>
  <acceptance_criteria>
    - `grep -q "AppLayout" resources/js/Pages/Sites/Index.vue` returns false.
    - `grep -q "defineOptions" resources/js/Pages/Sites/Index.vue` returns true.
    - `php artisan test --filter SitePagesTest` continues to pass (ensuring we didn't break page routing or missing properties).
  </acceptance_criteria>
</task>

## Verification
<must_haves>
  <truths>
    - Site CRUD pages use `defineOptions` for layout rendering.
    - Pages do not manually wrap their templates in `AppLayout`.
    - Empty state on the Sites Index page is styled natively.
  </truths>
  <prohibitions>
    - Do not create new UI templates if standard components (like `Heading`) exist.
  </prohibitions>
</must_haves>
