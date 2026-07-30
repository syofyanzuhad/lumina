---
wave: 1
depends_on: []
files_modified:
  - resources/js/tracker.js
  - public/js/script.js
  - package.json
  - packages/lumina-core/src/Http/Controllers/CollectController.php
  - packages/lumina-core/src/LuminaCoreServiceProvider.php
  - routes/api.php
  - resources/js/Pages/Sites/Show.vue
  - tests/Feature/CollectEndpointTest.php
  - tests/Unit/TrackerScriptSizeTest.php
autonomous: true
---

# Phase 5 Plan: Tracking Script (Path B) & Ingest Endpoint

<threat_model>
- Data spoofing (fake events sent to ingest): Mitigated by mandatory domain lookup in `sites` table and input validation on path/metadata.
- DoS & Resource Exhaustion: Mitigated by `lumina_ip` (60 req/min) and `lumina_site` (300 req/min) rate limiters on `/api/collect`.
- Privacy violations: IP address is never stored; hashed with rotating daily salt via `hash('sha256', IP + UserAgent + dailySalt)`.
- Performance overhead on host sites: Minified script < 2KB, `defer` loading, non-blocking asynchronous `sendBeacon`/`fetch` requests.
</threat_model>

<tasks>

<task id="01-tracker-js-and-build" autonomous="true">
  <action>Create vanilla JS tracking script and minification pipeline</action>
  <description>Create `resources/js/tracker.js` in plain JavaScript (< 2KB minified/gzipped). Read `data-domain` from the script tag. Implement initial pageview tracking, custom event queue (`window.lumina`), SPA navigation listeners (`router.on('navigate')` for Inertia, `popstate`, and `pushState` wrappers). Add `terser` to `package.json` devDependencies and script `"build:tracker": "terser resources/js/tracker.js -o public/js/script.js -c -m"`. Compile `public/js/script.js`.</description>
  <read_first>package.json</read_first>
  <requirements>SCRIPT-01, SCRIPT-02, SCRIPT-03, SCRIPT-04, SCRIPT-05</requirements>
  <acceptance_criteria>Script is vanilla JS with no external dependencies. `npm run build:tracker` generates `public/js/script.js` which is < 2KB minified and gzipped.</acceptance_criteria>
</task>

<task id="02-collect-controller-and-route" autonomous="true">
  <action>Create CollectController and register public POST /api/collect route</action>
  <description>Create `CollectController` in `packages/lumina-core/src/Http/Controllers/CollectController.php`. Validate `domain` (required, string), `path` (required, string), `referrer` (nullable), `screen_width` (nullable, integer), `name` (nullable, string), `metadata` (nullable, array). Reject unregistered domains with 422. Generate daily salt visitor hash, resolve device type from screen width (or user-agent fallback), extract country header, and dispatch `InsertEvent` job. Register `POST /api/collect` in `routes/api.php` with `throttle:lumina_ip,lumina_site` rate limiting.</description>
  <read_first>packages/lumina-core/src/LuminaCoreServiceProvider.php</read_first>
  <requirements>INGEST-01, INGEST-02, INGEST-03, INGEST-04, INGEST-05, SITE-05, PRIV-01, PRIV-02</requirements>
  <acceptance_criteria>Public route `POST /api/collect` accepts valid domain events, applies rate limits, dispatches `InsertEvent` job, and returns 204 No Content fast. Unregistered domains return 422.</acceptance_criteria>
</task>

<task id="03-snippet-ui-and-tests" autonomous="true">
  <action>Update tracking snippet display and write feature & unit tests</action>
  <description>Update `resources/js/Pages/Sites/Show.vue` snippet box if needed to match `<script defer data-domain="..." src="/js/script.js"></script>`. Create `tests/Feature/CollectEndpointTest.php` testing pageview collection, unregistered domain rejection, custom event metadata, and rate limiting. Create `tests/Unit/TrackerScriptSizeTest.php` asserting `public/js/script.js` gzipped size is strictly under 2048 bytes.</description>
  <read_first>resources/js/Pages/Sites/Show.vue</read_first>
  <requirements>SITE-02, SCRIPT-02, INGEST-01, INGEST-02, INGEST-05</requirements>
  <acceptance_criteria>All Pest feature tests and unit size checks pass cleanly.</acceptance_criteria>
</task>

</tasks>

## Artifacts this phase produces
- Script source: `resources/js/tracker.js`
- Compiled static script: `public/js/script.js`
- Controller: `packages/lumina-core/src/Http/Controllers/CollectController.php`
- Route: `POST /api/collect` in `routes/api.php`
- Test: `tests/Feature/CollectEndpointTest.php`
- Test: `tests/Unit/TrackerScriptSizeTest.php`

## Verification Criteria
- `public/js/script.js` must be < 2KB minified and gzipped.
- `POST /api/collect` returns 204 for registered sites and 422 for unregistered sites.
- Rate limiters `lumina_ip` and `lumina_site` are enforced on `/api/collect`.
- `InsertEvent` queue job receives valid parameters (visitor hash, device type, path, referrer, metadata).
- `php artisan test --compact` passes completely.

## must_haves
- Vanilla JS only, zero dependencies in `tracker.js`.
- Raw IP addresses must NEVER be saved directly to the database.
- `CollectController` must return 204 No Content fast without waiting for DB writes.
