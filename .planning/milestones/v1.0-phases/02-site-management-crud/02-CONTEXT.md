# Phase 2: Site Management (CRUD) - Context

**Gathered:** 2026-07-26T12:53:28Z
**Status:** Ready for planning

<domain>
## Phase Boundary

This phase delivers the core CRUD capability for managing analytics sites. It covers registering a new site, validating its domain, providing the tracking snippet via an onboarding flow, listing registered sites, and allowing the user to switch between active sites in the dashboard UI. This lays the foundation before we implement the ingest endpoint and tracking script in later phases.

</domain>

<decisions>
## Implementation Decisions

### Domain Validation
- **D-01:** Enforce strict formatting (strip protocols like http/https, 'www.', and trailing slashes). The domain should be stored in a clean format (e.g., 'example.com') to ensure consistent event matching later. — **Reversibility:** costly — Requires migrating existing database rows to the new format if we change validation rules later.

### Snippet Delivery & Onboarding
- **D-02:** Use a dedicated onboarding page. After adding a site, redirect to a full-page screen showing the snippet to ensure the user doesn't miss it. — **Reversibility:** reversible

### Dashboard Navigation / Site Switching
- **D-03:** Implement a global dropdown in the top navbar. The user can switch sites from anywhere via this dropdown, and the active site state is persisted (e.g., in the session or URL). — **Reversibility:** reversible

### the agent's Discretion
None

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### General Requirements
- `.planning/PROJECT.md` — Core value, technical constraints, and business context
- `.planning/REQUIREMENTS.md` § v1 Requirements -> Site Management — Specific SITE-01 to SITE-05 requirements
- `.planning/REQUIREMENTS.md` § v1 Requirements -> Data Model & Compatibility — Specific DATA-01, DATA-03, DATA-04 requirements

### Architecture & Standards
- `.planning/codebase/CONVENTIONS.md` — Strict coding standards, ESLint, Pint, PHPStan
- `.planning/codebase/STACK.md` — Tailwind v4, Vue 3, Inertia.js, Reka UI, Lucide Vue

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- `app/Models/User.php`: Owner of the sites (1:N relationship).
- Fortify/Inertia base components: Use existing navbar structure for the global site switcher dropdown.

### Established Patterns
- Monolith architecture using Laravel and Vue 3 + Inertia.js.
- Tailwind v4 styling with Reka UI for headless components (e.g., dropdowns).

### Integration Points
- Top navbar needs a site switcher component that reads the currently active site and allows changing it.
- Site registration form integrates with standard Laravel validation.

</code_context>

<specifics>
## Specific Ideas

No specific requirements — open to standard approaches

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope

</deferred>

---

*Phase: 02-Site Management (CRUD)*
*Context gathered: 2026-07-26T12:53:28Z*
