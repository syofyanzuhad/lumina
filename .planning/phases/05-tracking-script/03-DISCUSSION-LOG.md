# Phase 3: Tracking Script - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-07-29
**Phase:** 03-tracking-script
**Areas discussed:** Script identification strategy, Build & serving approach, Custom events API shape

---

## Script Identification Strategy

| Option | Description | Selected |
|--------|-------------|----------|
| data-domain (current approach) | Script uses `data-domain="example.com"`. Ingest looks up site by domain. Simple, matches Show.vue. | ✓ |
| tracking_token (decoupled) | Add tracking_token column to sites. Script uses `data-token="site_abc123"`. Decouples ID from domain. | |

**User's choice:** data-domain (current approach)
**Notes:** User confirmed keeping the existing `data-domain` pattern already in Show.vue. No migration needed. Keeps snippet simple.

---

## Build & Serving Approach

| Option | Description | Selected |
|--------|-------------|----------|
| Hand-written vanilla JS | `resources/js/tracker.js` with no imports. Minified via build step. Simplest, smallest output. | ✓ |
| Vite entry point (TypeScript) | Separate Vite entry for tracker. TypeScript, tree-shaking, consistent pipeline. Risk of exceeding 2KB. | |

**User's choice:** Hand-written vanilla JS
**Notes:** Simplest path to staying under 2KB. No Vite module wrapper overhead.

### Serving sub-decision

| Option | Description | Selected |
|--------|-------------|----------|
| Laravel route (dynamic) | Route returns minified JS with cache headers. PHP overhead per request (mitigated by browser caching). | |
| Static file in public/ | Build step copies to `public/js/script.js`. Zero PHP overhead, CDN-friendly. | ✓ |

**User's choice:** Static file in public/ — "we will use a static so we can put it on CDN"
**Notes:** CDN-friendly serving was the deciding factor. Zero PHP overhead, web server / CDN serves directly.

---

## Custom Events API Shape

| Option | Description | Selected |
|--------|-------------|----------|
| Flat key-value (string/number only) | `window.lumina('purchase', { amount: 29.99 })`. Simplest validation, smallest payload. | |
| Nested objects allowed | `window.lumina('purchase', { product: { name: 'Widget', price: 29.99 }, quantity: 1 })`. More expressive, larger payloads. | ✓ |

**User's choice:** Nested objects allowed
**Notes:** User chose nested objects for expressiveness. Phase 4 validation will need to handle nested JSON.

### Storage sub-decision

| Option | Description | Selected |
|--------|-------------|----------|
| JSON column on events table | `metadata` JSON column. Null for pageviews. Single table, single ingest path. | ✓ (Claude's discretion) |
| Separate custom_events table | Dedicated table for custom events. Cleaner separation, faster queries per type. More complex. | |

**User's choice:** You decide (deferred to Claude)
**Notes:** Claude chose JSON column on events table — simpler schema, single ingest path, consistent with "avoid premature abstraction" principle. Can migrate to separate table in v2 if needed.

---

## Pre-locked Decisions (from project-en.md)

- Script listens for Inertia `router.on('navigate')` + `history.pushState`/`popstate` for SPA navigation (locked by §3.1)
- Script is vanilla JS, no dependencies, < 2KB (locked by §2)
- Sends: page URL, referrer, screen width, timestamp — no cookies, no fingerprint (locked by §3.1)

---

## Claude's Discretion

- D-05: Custom event storage — JSON column on events table (user deferred, Claude decided based on simplicity and project principles)

## Deferred Ideas

None — discussion stayed within phase scope