---
phase: 16
slug: milestone-v1-1-verification-e2e
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-31
---

# Phase 16 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest PHP v4 / PHPUnit v12 |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact --filter=MilestoneV11Test` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~15 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact --filter=MilestoneV11Test`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 20 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 16-01-01 | 01 | 1 | REQ-VFY-01 | — | Full E2E pipeline verification | integration | `php artisan test --compact --filter=MilestoneV11Test` | ❌ W0 | ⬜ pending |
| 16-01-02 | 01 | 1 | REQ-VFY-01 | — | Zero regressions across existing suite | regression | `php artisan test --compact` | ✅ | ⬜ pending |
| 16-02-01 | 02 | 2 | REQ-VFY-01 | — | Documentation completeness | doc | `test -f README.md && test -f packages/lumina-core/README.md` | ✅ | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/MilestoneV11Test.php` — stub for REQ-VFY-01 end-to-end integration scenario

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|

*All phase behaviors have automated verification.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 20s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
