---
phase: 02
slug: site-management-crud
status: validated
nyquist_compliant: true
wave_0_complete: true
created: 2026-07-26
---

# Phase 02 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest (PHPUnit wrapper) |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact` |
| **Full suite command** | `php artisan test` |
| **Estimated runtime** | ~3 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact`
- **After every plan wave:** Run `php artisan test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 5 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| task-01-tracer | 02-PLAN | 1 | SITE-01 | T2 | N/A | integration | `php artisan test --filter SiteControllerTest` | ✅ | ✅ green |
| task-02-policy | 02-PLAN | 1 | SITE-02 | T1 | SitePolicy | integration | `php artisan test --filter SitePolicyTest` | ✅ | ✅ green |
| task-03-active-site-middleware | 02-PLAN | 1 | SITE-03 | - | N/A | integration | `php artisan test --filter ActiveSiteControllerTest` | ✅ | ✅ green |
| task-04-site-switcher-ui | 02-PLAN | 2 | SITE-04 | - | N/A | integration | `php artisan test --filter SiteSwitcherTest` | ✅ | ✅ green |
| task-05-site-pages | 02-PLAN | 2 | SITE-01, SITE-02 | T3 | Vue escaping | integration | `php artisan test --filter SitePagesTest` | ✅ | ✅ green |

---

## Wave 0 Requirements

Existing infrastructure covers all phase requirements.

---

## Manual-Only Verifications

All phase behaviors have automated verification.

---

## Validation Audit 2026-07-26
| Metric | Count |
|--------|-------|
| Gaps found | 3 |
| Resolved | 3 |
| Escalated | 0 |

---

## Validation Sign-Off

- [x] All tasks have automated verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 5s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** approved 2026-07-26
