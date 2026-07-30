# Phase 5: Tracking Script (Path B) & Ingest Endpoint — Research

**Gathered:** 2026-07-30
**Phase:** 05 — Tracking Script (Path B) & Ingest Endpoint
**Status:** Completed

---

## 1. Domain Architecture & Boundaries

Phase 5 delivers the standalone client-side tracking script and public API ingest endpoint for Lumina:
1. **Frontend Tracker Script (`resources/js/tracker.js` → `public/js/script.js`)**:
   - Vanilla JS without dependencies.
   - Size gate: `< 2KB` minified and gzipped.
   - Serves from `public/js/script.js` as a static file (CDN-friendly).
   - Collects `domain`, `path`, `referrer`, `screen_width`.
   - Supports SPA navigation (Inertia `router.on('navigate')`, `pushState`, `popstate`).
   - Supports custom events (`window.lumina('event_name', { props })`).
2. **Ingest Endpoint (`POST /api/collect`)**:
   - Controller (`CollectController`) in `packages/lumina-core/src/Http/Controllers/` or app context.
   - Public endpoint (unauthenticated).
   - Validates `domain` against `sites` table. Rejects unregistered sites (INGEST-02 / SITE-05).
   - Calculates `visitor_hash` using standard daily salt formula: `hash('sha256', IP + UserAgent + dailySalt)`.
   - Derives `device_type` from `screen_width` bucket using `DeviceType::fromScreenWidth($width)`.
   - Rate limited per IP (`60/min`) and per site (`300/min`) using existing `lumina_ip` and `lumina_site` limiters.
   - Dispatches `InsertEvent` queue job (INGEST-03) and returns 204 No Content fast (INGEST-04).

---

## 2. Requirements & Verification Mapping

| Requirement | Description | Implementation Strategy |
|---|---|---|
| **SCRIPT-01** | Vanilla JS, no external dependencies | Written in pure ES5/ES6 in `resources/js/tracker.js`. |
| **SCRIPT-02** | Bundle `< 2KB` minified & gzipped | Terser minification step `npm run build:tracker`. Test assertion verifies gzipped byte size. |
| **SCRIPT-03** | Payload: page URL, referrer, screen width, timestamp | Sent via `navigator.sendBeacon` or `fetch` with `keepalive: true`. |
| **SCRIPT-04** | Async & non-blocking | Loaded with `defer`, fire-and-forget logic with silent error swallowing. |
| **SCRIPT-05** | Custom events API `window.lumina()` | Event queue buffer before load (`window.lumina.q`), sends event payload with `metadata`. |
| **INGEST-01** | `POST /api/collect` public | Unauthenticated route in `routes/api.php`. |
| **INGEST-02** | Payload validation & domain check | `CollectRequest` or controller validation; checks domain exists in `sites` table. |
| **INGEST-03** | Dispatch to `InsertEvent` queue job | Calls `InsertEvent::dispatch(...)` asynchronously. |
| **INGEST-04** | Fast response | Decoupled queue write; returns 204 No Content immediately. |
| **INGEST-05** | Rate limiting per IP & site | Uses `throttle:lumina_ip,lumina_site` on `/api/collect` route. |

---

## 3. Data Flow & Security Design

### Payload Schema (`POST /api/collect`):
```json
{
  "domain": "example.com",
  "path": "/pricing",
  "referrer": "https://google.com",
  "screen_width": 1440,
  "name": "checkout_click",
  "metadata": { "plan": "pro", "price": 29 }
}
```

### Response:
- Success: `204 No Content`
- Rate Limited: `429 Too Many Requests`
- Invalid / Unregistered Domain: `422 Unprocessable Content` (or `404 Not Found`)

---

## 4. Script & Pipeline Patterns

1. **Tracker Snippet Tag**:
   ```html
   <script defer data-domain="example.com" src="https://your-lumina.com/js/script.js"></script>
   ```

2. **Custom Event Interface**:
   ```js
   window.lumina = window.lumina || function() { (window.lumina.q = window.lumina.q || []).push(arguments); };
   window.lumina('signup', { plan: 'pro' });
   ```

3. **Build Command**:
   Install `terser` as a dev dependency, add script `"build:tracker": "terser resources/js/tracker.js -o public/js/script.js -c -m"`.
