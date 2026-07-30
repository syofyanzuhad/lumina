# Phase 02 Research: Site Management (CRUD)

## 1. Context & Goals
The goal of this phase is to build the core CRUD capabilities for managing analytics sites. This lays the foundation for tracking events by providing a way to register domains and generate tracking snippets.

**Requirements Addressed:**
- **SITE-01**: User can register a new site with a domain name.
- **SITE-02**: User receives a unique tracking snippet (script tag) after registering.
- **SITE-03**: User can view a list of all their registered sites.
- **SITE-04**: User can switch between sites in the dashboard.
- **DATA-01**: `sites` table schema (`id`, `domain`, `owner_id`, `created_at`).
- **DATA-03**: PostgreSQL and MySQL compatible schema queries.

## 2. Implementation Approach

### 2.1 Database & Model Layer
- **Migration**: Create a `sites` table with:
  - `id` (Primary Key)
  - `domain` (String, unique per user to prevent duplicates)
  - `owner_id` (Foreign ID constrained to `users` table)
  - `created_at` / `updated_at` (Timestamps)
- **Model (`Site`)**: Define a `belongsTo` relationship to the `User` model, and a `hasMany` relationship for sites on the `User` model.

### 2.2 Domain Normalization & Validation (Decision D-01)
- We must enforce strict formatting for domains to guarantee consistent event matching later.
- **Action**: Implement a normalization step before validation/creation.
- **Logic**: Strip `http://`, `https://`, `www.`, and trailing slashes. E.g., `https://www.example.com/` becomes `example.com`.
- **Validation**: Use a Form Request (`StoreSiteRequest`) to validate that the stripped input remains a valid domain format.

### 2.3 Snippet Delivery & Onboarding (Decision D-02)
- Instead of returning to an index list, successful creation should redirect the user to a dedicated onboarding page (e.g., `SiteController@show`).
- **UI**: Display the HTML `<script>` tracking snippet clearly with a "Copy to Clipboard" button (using Vue/Reka UI).

### 2.4 Active Site Switching (Decision D-03)
- Users need a way to switch their active site globally.
- **State**: Store the `active_site_id` in the user's session. If no site is active, default to the user's first created site (if any).
- **UI component**: Build a global dropdown in the top navbar.
- **Inertia integration**: Use the `HandleInertiaRequests` middleware to globally share:
  1. The user's list of sites (id and domain).
  2. The currently active site.
- **Endpoint**: Create a route/controller (e.g., `ActiveSiteController@update`) to update the session and redirect back.

### 2.5 Authorization Policy
- Generate a `SitePolicy`.
- Ensure users can only view, manage, and delete sites they own (`$site->owner_id === $user->id`).

### 2.6 Testing Strategy
- Leverage **Pest 4** for all testing needs.
- **Feature Tests**:
  - `SiteControllerTest`: Assert CRUD operations, domain normalization, validation errors, and onboarding redirect.
  - `SitePolicyTest`: Assert users cannot view or delete other users' sites.
  - `ActiveSiteControllerTest`: Assert session updates correctly and handles invalid site IDs.
- Ensure all tests adhere to project constraints (`pest-testing` skill: use datasets for domain normalization tests, `assertSuccessful()`, `assertNotFound()`).

## 3. Tech Stack & Conventions Reminders
- **Backend (PHP 8.3 / Laravel 13)**: 
  - Use constructor property promotion.
  - Ensure strict types and explicit return types.
  - Run `vendor/bin/pint --dirty --format agent` after modifications.
- **Frontend (Vue 3 / Inertia v3 / Tailwind v4)**:
  - Page components belong in `resources/js/pages`.
  - Prefer the Inertia `<Form>` component or `useForm` hook for form handling.
  - Build UI using headless Reka UI primitives styled with Tailwind v4.
  - Ensure all Vue files have a single root element.

## 4. Next Steps
1. Create Migration and `Site` Model.
2. Implement `SitePolicy` and register it.
3. Build `SiteController` and `ActiveSiteController`.
4. Update `HandleInertiaRequests` middleware.
5. Create Vue pages (`Sites/Index`, `Sites/Create`, `Sites/Show` for onboarding).
6. Implement the Site Switcher Navbar component.
7. Write and run Pest tests to ensure full coverage.
