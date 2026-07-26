---
status: findings
critical: 1
warning: 4
info: 1
total: 6
---

# Phase 2 Code Review

## Findings

### BLOCKER: Case-sensitive regex in `StoreSiteRequest` allows dirty data
In `StoreSiteRequest::prepareForValidation()`, the `preg_replace` patterns for stripping the protocol and `www.` are missing the case-insensitive modifier (`/i`). 
If a user submits `WwW.example.com`, the `^www\.` pattern fails to match. The string passes through to `strtolower`, ultimately saving `www.example.com` to the database. This allows dirty data and potentially bypasses uniqueness constraints. Similarly, inputs like `HTTPS://example.com` fail validation because the protocol isn't stripped.
**Fix**: Update the regex patterns to `#^https?://#i` and `#^www\.#i`.

### WARNING: Information Disclosure / Enumerable IDs in `ActiveSiteController`
The validation rule `exists:sites,id` in `ActiveSiteController` checks the entire database globally. If a user submits another user's `site_id`, it passes validation and later throws a 404 at `$request->user()->sites()->findOrFail()`. A truly non-existent ID returns a 422. This discrepancy allows a user to enumerate valid site IDs across the entire system.
**Fix**: Scope the validation rule to the user's sites: `Rule::exists('sites', 'id')->where('owner_id', $request->user()->id)`.

### WARNING: Missing session update on Active Site fallback
In `HandleInertiaRequests`, if the currently active site no longer exists (e.g., it was deleted), the middleware falls back to `$sites->first()->id`. However, it does not persist this fallback ID back to the session. This means the fallback evaluation logic will run on every subsequent request instead of healing the session state.
**Fix**: If a fallback is triggered, write the new active site ID to the session.

### WARNING: Hardcoded route URLs in Vue templates
In `resources/js/pages/Sites/Index.vue`, the links to view site details use hardcoded paths (``:href="`/sites/${site.id}`"``) instead of leveraging the Laravel Wayfinder plugin used elsewhere in the file (like `destroy.url()`).
**Fix**: Use `show.url({ site: site.id })` for consistency and resilience against route changes.

### WARNING: Ambiguous domain uniqueness scoping
The `StoreSiteRequest` uses a global uniqueness rule: `Rule::unique('sites', 'domain')`. However, the test `SiteControllerTest.php` defines a test named `it requires a unique domain per user`. 
If domains are meant to be unique *per user*, the validation rule is missing the `->where('owner_id', ...)` constraint. If domains are globally unique, the test name is misleading and lacks an assertion proving that one user cannot claim a domain registered by a *different* user.

### INFO: UX Improvement for Site Creation
In `SiteController@store`, creating a new site correctly saves it and redirects to the show page. However, it does not set the newly created site as the active site in the session. The user must manually switch to it in the sidebar to interact with it fully. 
**Fix**: Consider calling `session()->put('active_site_id', $site->id);` inside the `store` method.
