---
phase: 12
slug: custom-event-tracking-ui-breakdown
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-30
---

# Phase 12 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest 4 / PHPUnit 12 |
| **Config file** | phpunit.xml |
| **Quick run command** | `vendor/bin/pest packages/lumina-core/tests/Feature/AnalyticsServiceTest.php` |
| **Full suite command** | `vendor/bin/pest` |
| **Estimated runtime** | ~5 seconds |

---

## Sampling Rate

- **After every task commit:** Run `vendor/bin/pest packages/lumina-core/tests/Feature/AnalyticsServiceTest.php`
- **After every plan wave:** Run `vendor/bin/pest`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 10 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 12-01-01 | 01 | 1 | Custom event analytics backend | — | N/A | feature | `vendor/bin/pest packages/lumina-core/tests/Feature/AnalyticsServiceTest.php` | ✅ | ⬜ pending |
| 12-01-02 | 01 | 1 | Inertia Custom Events UI | — | N/A | feature | `vendor/bin/pest tests/Feature/DashboardControllerTest.php` | ✅ | ⬜ pending |
| 12-01-03 | 01 | 1 | Livewire Custom Events UI | — | N/A | feature | `vendor/bin/pest packages/lumina-core/tests/Feature/LivewireDashboardTest.php` | ✅ | ⬜ pending |

---

## Wave 0 Requirements

Existing infrastructure covers all phase requirements.

---

## Manual-Only Verifications

All phase behaviors have automated verification.

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references
- [x] No watch-mode flags
- [x] Feedback latency < 10s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
