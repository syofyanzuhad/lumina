# Phase 2: Site Management (CRUD) - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-07-26T12:53:28Z
**Phase:** 02-site-management-crud
**Areas discussed:** Domain validation strictness, Snippet delivery & onboarding, Dashboard navigation/site switching

---

## Domain validation strictness

| Option | Description | Selected |
|--------|-------------|----------|
| Strict formatting | Strip protocols (http/https), 'www.', and trailing slashes. Enforce a clean domain format (e.g., 'example.com'). | ✓ |
| Allow raw user input | Save exactly what they type. (Warning: Makes event matching harder later.) | |

**User's choice:** Strict formatting — Strip protocols (http/https), 'www.', and trailing slashes. Enforce a clean domain format (e.g., 'example.com').
**Notes:** N/A

---

## Snippet delivery & onboarding

| Option | Description | Selected |
|--------|-------------|----------|
| Dedicated onboarding page | After adding a site, redirect to a full-page screen showing the snippet. Ensures they don't miss it. | ✓ |
| Modal/Inline | Show the snippet in a modal or expandable section right on the site list. Faster, less navigation. | |

**User's choice:** Dedicated onboarding page — After adding a site, redirect to a full-page screen showing the snippet. Ensures they don't miss it.
**Notes:** N/A

---

## Dashboard navigation/site switching

| Option | Description | Selected |
|--------|-------------|----------|
| Global dropdown in top navbar | The user can switch sites from anywhere via a dropdown. The active site is persisted (e.g., in session or URL). | ✓ |
| "Select Site" landing page | The user logs in, sees a list of sites, clicks one, and goes to its specific dashboard. Navigating back requires clicking a "back to sites" button. | |

**User's choice:** Global dropdown in top navbar — The user can switch sites from anywhere via a dropdown. The active site is persisted (e.g., in session or URL).
**Notes:** N/A

---

## the agent's Discretion

None

## Deferred Ideas

None
