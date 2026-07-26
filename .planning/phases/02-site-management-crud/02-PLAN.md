---
wave: 1
depends_on: []
files_modified: []
autonomous: true
---

# Phase 02 Plan: Site Management (CRUD)

<threat_model>
ASVS Level: L1
Block on: High

Threats:
- **Broken Access Control**: Users managing sites they do not own. Mitigated by SitePolicy.
- **Invalid Input**: Malformed domains bypassing validation. Mitigated by domain normalization and StoreSiteRequest validation.
- **Cross-Site Scripting (XSS)**: Malicious domain names injected into tracking snippets or lists. Mitigated by Vue's auto-escaping and strict formatting validation.
</threat_model>

## Objective
Deliver core CRUD capabilities for managing analytics sites, including domain validation, onboarding snippets, and global active site switching. 

**Phase requirement IDs**: SITE-01, SITE-02, SITE-03, SITE-04
**Spec-less probe fallback**: ⚠ spec-less probe fallback skipped: phase has no requirement IDs to probe (visible skip).

## Artifacts this phase produces
- `App\Http\Controllers\SiteController`
- `App\Http\Controllers\ActiveSiteController`
- `App\Policies\SitePolicy`
- `App\Http\Requests\StoreSiteRequest`
- `resources/js/pages/Sites/Index.vue`
- `resources/js/pages/Sites/Create.vue`
- `resources/js/pages/Sites/Show.vue`
- `resources/js/components/SiteSwitcher.vue`
- `tests/Feature/SiteControllerTest.php`
- `tests/Feature/SitePolicyTest.php`
- `tests/Feature/ActiveSiteControllerTest.php`

## Assumptions
- E5: Delete site confirmation dialog UI behavior is unresolved in UI-SPEC; assuming standard browser confirm or simple dialog without complex states for MVP.

## Execution Plan

### Wave 1: Tracer & Core Backend Setup

<task type="tracer" id="task-01-tracer">
  <name>Tracer: End-to-End Site Registration</name>
  <description>Establish the vertical slice for creating a site (Request -> Controller -> DB) and validation.</description>
  <read_first>
    - app/Models/Site.php
    - app/Models/User.php
    - database/migrations/2026_07_26_111908_create_sites_table.php
    - routes/web.php
    - app/Http/Controllers/SiteController.php (if exists)
    - app/Http/Requests/StoreSiteRequest.php (if exists)
  </read_first>
  <action>
    - Create `App\Http\Requests\StoreSiteRequest` with a `prepareForValidation` method that strips `http://`, `https://`, `www.`, and trailing slashes from the `domain` input. Validate domain format and uniqueness for the user (D-01).
    - Create `App\Http\Controllers\SiteController` with `store` method. It should create the site for `$request->user()`.
    - Add POST `/sites` route in `routes/web.php` protected by `auth` middleware.
    - Create `tests/Feature/SiteControllerTest.php` using `php artisan make:test --pest SiteControllerTest` asserting that a normalized valid domain creates a site and returns a redirect.
  </action>
  <acceptance_criteria>
    - `php artisan test --filter SiteControllerTest` passes.
    - `StoreSiteRequest` strips URL protocols and paths before validating `domain`.
    - `POST /sites` successfully inserts a record into the `sites` table.
  </acceptance_criteria>
</task>

<task id="task-02-policy">
  <name>Site Policy & Authorization</name>
  <description>Ensure users can only view, manage, and delete their own sites.</description>
  <read_first>
    - app/Models/Site.php
  </read_first>
  <action>
    - Create `App\Policies\SitePolicy` mapped to `App\Models\Site`.
    - Implement `view`, `update`, and `delete` methods enforcing `$user->id === $site->owner_id`.
    - Register policy if necessary (Laravel 11/13 auto-discovers if conventions match).
    - Create `tests/Feature/SitePolicyTest.php` using `php artisan make:test --pest SitePolicyTest` to assert a user cannot delete or view another user's site.
  </action>
  <acceptance_criteria>
    - `php artisan test --filter SitePolicyTest` passes.
    - Policy strictly checks `$site->owner_id`.
  </acceptance_criteria>
</task>

<task id="task-03-active-site-middleware">
  <name>Active Site Switcher State</name>
  <description>Provide global state for the active site in Inertia.</description>
  <read_first>
    - app/Http/Middleware/HandleInertiaRequests.php
    - routes/web.php
    - app/Http/Controllers/ActiveSiteController.php (if exists)
  </read_first>
  <action>
    - Create `App\Http\Controllers\ActiveSiteController` with an `update` method that expects a `site_id`, validates ownership, stores it in `session()->put('active_site_id', $site->id)`, and redirects back.
    - Add PUT `/sites/active` route in `routes/web.php`.
    - Update `HandleInertiaRequests::share()` to append the user's `sites` (id and domain) and `active_site_id` (fallback to user's first site if session empty).
    - Create `tests/Feature/ActiveSiteControllerTest.php` using `php artisan make:test --pest ActiveSiteControllerTest`.
  </action>
  <acceptance_criteria>
    - `php artisan test --filter ActiveSiteControllerTest` passes.
    - `HandleInertiaRequests` exposes `sites` and `active_site_id` payload to the frontend.
    - `PUT /sites/active` properly updates the session.
  </acceptance_criteria>
</task>

### Wave 2: Frontend Pages & Components

<task id="task-04-site-switcher-ui">
  <name>Global Site Switcher UI Component</name>
  <description>Create the dropdown component for switching sites globally (D-03).</description>
  <read_first>
    - resources/js/components/NavMain.vue
    - .planning/phases/02-site-management-crud/02-UI-SPEC.md
  </read_first>
  <action>
    - Create `resources/js/components/SiteSwitcher.vue`.
    - Read `usePage().props.sites` and `usePage().props.active_site_id` to populate a select/dropdown using Reka UI / shadcn.
    - On change, submit a request to update the active site using Wayfinder's typed route function (e.g., `import { update } from '@/actions/App/Http/Controllers/ActiveSiteController'`) instead of a hardcoded PUT path.
    - Add `SiteSwitcher.vue` to the main navigation layout (e.g., `NavMain.vue` or equivalent layout wrapper).
  </action>
  <acceptance_criteria>
    - Dropdown shows list of user's sites.
    - Changing the selection triggers a request using Wayfinder route functions that correctly updates the session and reloads the page.
    - Uses shadcn/Reka UI primitives styled with Tailwind v4.
  </acceptance_criteria>
</task>

<task id="task-05-site-pages">
  <name>Site CRUD Pages (Index, Create, Show)</name>
  <description>Implement the frontend pages for listing, creating, and onboarding a new site (D-02).</description>
  <read_first>
    - resources/js/pages/settings/Profile.vue
    - .planning/phases/02-site-management-crud/02-UI-SPEC.md
  </read_first>
  <action>
    - Complete `SiteController` `index`, `create`, `show`, `destroy` methods rendering Inertia pages.
    - Create `resources/js/pages/Sites/Index.vue`: Displays user's sites. Render empty state "No sites found" if none.
    - Create `resources/js/pages/Sites/Create.vue`: Form for adding a site, binds to Wayfinder's `store` route function (e.g., `import { store } from '@/actions/App/Http/Controllers/SiteController'`). Redirects to `SiteController@show` on success.
    - Create `resources/js/pages/Sites/Show.vue`: Onboarding page displaying the `<script>` tag tracking snippet with a copy-to-clipboard button.
    - Create/implement `Delete` site action on the `Index` page using Wayfinder route functions.
  </action>
  <acceptance_criteria>
    - `Index.vue` displays empty state copy "No sites found" when appropriate.
    - `Create.vue` leverages `@inertiajs/vue3` Form helper for validation errors and uses Wayfinder for the form submission route.
    - `Show.vue` properly displays the tracking snippet code.
    - Controller routes return the correct Inertia views.
  </acceptance_criteria>
</task>

## Verification

```yaml
must_haves:
  truths:
    - "Empty results render the documented 'No sites found' copy"
    - statement: "E1: Site registration form properly handles empty state"
      verification: backstop
    - statement: "E1: Site registration form properly handles loading state"
      verification: backstop
    - statement: "E1: Site registration form properly handles error state"
      verification: backstop
    - statement: "E1: Site registration form properly handles partial state"
      verification: backstop
    - statement: "E1: Site registration form properly handles long-text state"
      verification: backstop
    - statement: "E2: Tracking snippet display properly handles overflow state"
      verification: backstop
    - statement: "E2: Tracking snippet display properly handles long-text state"
      verification: backstop
    - statement: "E3: Site list page with switcher properly handles loading state"
      verification: backstop
    - statement: "E3: Site list page with switcher properly handles error state"
      verification: backstop
    - statement: "E3: Site list page with switcher properly handles populated state"
      verification: backstop
    - statement: "E3: Site list page with switcher properly handles partial state"
      verification: backstop
    - statement: "E3: Site list page with switcher properly handles overflow state"
      verification: backstop
    - statement: "E3: Site list page with switcher properly handles zero-one-many state"
      verification: backstop
    - statement: "E4: Primary CTA button properly handles long-text state"
      verification: backstop
  prohibitions: []
```
