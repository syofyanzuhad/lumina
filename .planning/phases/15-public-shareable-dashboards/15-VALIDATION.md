---
phase: 15
slug: public-shareable-dashboards
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-31
---

# Phase 15 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest PHP |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --filter=ShareControllerTest` |
| **Full suite command** | `php artisan test` |
| **Estimated runtime** | ~5 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=ShareControllerTest`
- **After every plan wave:** Run `php artisan test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 5 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 15-01-01 | 01 | 1 | REQ-SHARE-01 | — | N/A | unit | `php artisan test --filter=SiteModelTest` | ❌ W0 | ⬜ pending |
| 15-01-02 | 01 | 1 | REQ-SHARE-02 | — | N/A | feature | `php artisan test --filter=ShareControllerTest` | ❌ W0 | ⬜ pending |
| 15-01-03 | 01 | 1 | REQ-SHARE-03 | — | Password Auth | feature | `php artisan test --filter=ShareControllerTest` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/ShareControllerTest.php` — stubs for REQ-SHARE-01, REQ-SHARE-02, REQ-SHARE-03

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Read-only parity | REQ-SHARE-02 | Visual verification | Open `/share/{token}` and verify no site settings links or export buttons are visible. |

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 5s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
