---
wave: 2
depends_on:
  - 15-1-backend-PLAN.md
files_modified:
  - resources/js/Pages/Sites/Show.vue
  - resources/js/Pages/Share/Show.vue
  - tests/Feature/ShareControllerTest.php
autonomous: true
---

# Phase 15: Public & Shareable Dashboards (Frontend)

## requirements
- REQ-SHARE-01
- REQ-SHARE-02
- REQ-SHARE-03
- REQ-VFY-01

## must_haves
```yaml
truths:
  - "Site settings page displays a sharing configuration card."
  - "The public dashboard UI renders analytics cards but hides admin controls."
  - { statement: "Public dashboard UI gates access behind a password form if requiresPassword is true.", verification: backstop }
prohibitions:
  - statement: "Public viewers MUST NOT have access to export actions or site settings links."
```

## tasks

```xml
<task>
  <id>1</id>
  <name>Site Settings Share Management UI</name>
  <read_first>
    <file>resources/js/Pages/Sites/Show.vue</file>
  </read_first>
  <action>
    Update resources/js/Pages/Sites/Show.vue to include a "Public Sharing" management card.
    The card must include a toggle to enable/disable sharing (bound to is_public), an input displaying the share URL (e.g., /share/TOKEN) with a copy button, a "Regenerate Token" button that submits a POST to /sites/{site}/share/regenerate, and an optional password input to set share_password via PUT to /sites/{site}/share.
  </action>
  <acceptance_criteria>
    resources/js/Pages/Sites/Show.vue contains references to the /sites/{site}/share and /sites/{site}/share/regenerate endpoints.
    Form submits toggling is_public using Inertia forms.
  </acceptance_criteria>
</task>

<task>
  <id>2</id>
  <name>Public Share Dashboard UI</name>
  <read_first>
    <file>resources/js/Pages/Dashboard.vue</file>
  </read_first>
  <action>
    Create resources/js/Pages/Share/Show.vue.
    If the 'requiresPassword' prop is true, render a password form that posts to /share/{token}/password.
    Otherwise, render the analytics dashboard (reusing components like metric cards, charts, and tables from Dashboard.vue), but strip out the site switcher, export dropdown, settings link, and live refresh toggles. Use a simplified layout with a "Powered by Lumina" badge instead of the admin navigation.
  </action>
  <acceptance_criteria>
    resources/js/Pages/Share/Show.vue exists.
    It conditionally renders a password form based on requiresPassword prop.
    It does NOT import or render the admin Site Switcher or Export dropdown components.
  </acceptance_criteria>
</task>

<task>
  <id>3</id>
  <name>Feature Tests for Share Endpoints</name>
  <read_first>
    <file>tests/Feature/DashboardControllerTest.php</file>
  </read_first>
  <action>
    Create tests/Feature/ShareControllerTest.php.
    Write tests verifying that: GET /share/{token} returns 200 and Inertia Share/Show for public sites; returns 404 for non-public sites; returns requiresPassword prop for password-protected sites.
    Write tests for POST /share/{token}/password correctly setting session flags.
    Write tests for the management endpoints (toggle is_public, regenerate token) ensuring they require authentication and correctly enforce policy authorization (unauthorized users attempting to manage a site they don't own MUST receive 403 Forbidden).
  </action>
  <acceptance_criteria>
    tests/Feature/ShareControllerTest.php exists.
    php artisan test --filter ShareControllerTest passes 100%.
  </acceptance_criteria>
</task>
```

## Artifacts this phase produces
- resources/js/Pages/Share/Show.vue
- tests/Feature/ShareControllerTest.php
