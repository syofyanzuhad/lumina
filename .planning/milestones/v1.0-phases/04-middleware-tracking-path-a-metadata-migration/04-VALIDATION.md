---
phase: 4
slug: middleware-tracking-path-a-metadata-migration
# status lifecycle: draft (seeded by plan-phase) → validated (set by validate-phase §6)
# audit-milestone §5.5 distinguishes NOT-VALIDATED (draft) from PARTIAL (validated + nyquist_compliant: false) (#2117)
status: draft
nyquist_compliant: false
wave_0_complete: false
created: 2026-07-30
---

# Phase 4 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest v4 |
| **Config file** | `phpunit.xml` |
| **Quick run command** | `php artisan test --compact` |
| **Full suite command** | `php artisan test --compact` |
| **Estimated runtime** | ~5 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --compact`
- **After every plan wave:** Run `php artisan test --compact`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 5 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 4-01-01 | 01 | 1 | INGEST-03 | T-4-01 | N/A | unit | `php artisan test --compact` | ❌ W0 | ⬜ pending |
| 4-01-02 | 01 | 1 | INGEST-01 | T-4-02 | N/A | integration | `php artisan test --compact` | ❌ W0 | ⬜ pending |
| 4-01-03 | 01 | 1 | PRIV-01 | T-4-03 | No raw IP stored | integration | `php artisan test --compact` | ❌ W0 | ⬜ pending |
| 4-01-04 | 01 | 1 | PRIV-02 | T-4-04 | Rotating salt | unit | `php artisan test --compact` | ❌ W0 | ⬜ pending |
| 4-01-05 | 01 | 1 | SITE-05 | T-4-05 | N/A | integration | `php artisan test --compact` | ❌ W0 | ⬜ pending |
| 4-01-06 | 01 | 1 | INGEST-05 | T-4-06 | Silent swallow on limit | integration | `php artisan test --compact` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/TrackPageviewMiddlewareTest.php` — stubs for Phase 4 requirements

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Cloud CDN Headers | N/A | CDN specific | N/A |

*If none: "All phase behaviors have automated verification."*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 5s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
