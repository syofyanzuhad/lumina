---
status: all_fixed
findings_in_scope: 5
fixed: 5
skipped: 0
iteration: 1
---

# Phase 2 Code Review Fixes

All critical and warning findings have been addressed:

1. **BLOCKER: Case-sensitive regex in StoreSiteRequest allows dirty data**
   - Fixed by adding the `/i` case-insensitive modifier to the `preg_replace` patterns in `StoreSiteRequest`.

2. **WARNING: Information Disclosure / Enumerable IDs in ActiveSiteController**
   - Fixed by scoping the `site_id` validation rule to the user's sites using `Rule::exists('sites', 'id')->where('owner_id', $request->user()->id)`.

3. **WARNING: Missing session update on Active Site fallback**
   - Fixed by persisting the `$activeSiteId` to the session using `session()->put('active_site_id', $activeSiteId)` when a fallback is triggered in `HandleInertiaRequests`.

4. **WARNING: Hardcoded route URLs in Vue templates**
   - Fixed by replacing hardcoded string interpolation with Wayfinder's `show.url({ site: site.id })` in `resources/js/pages/Sites/Index.vue`.

5. **WARNING: Ambiguous domain uniqueness scoping**
   - Fixed by scoping the unique validation rule for domains in `StoreSiteRequest` to be unique only per user, matching the intended behavior tested in `SiteControllerTest.php`.

*(Note: The INFO finding regarding setting the active site on creation was out of scope for this task and has been left as is.)*
