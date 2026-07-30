---
wave: 1
depends_on: []
files_modified:
  - tests/Feature/EndToEndVerificationTest.php
  - README.md
autonomous: true
---

# Phase 10 Plan: End-to-End Verification & Production Readiness

<threat_model>
- Deployment documentation gaps: Mitigated by comprehensive production `README.md` covering Docker Compose, Laravel Cloud, and embedded package modes.
- Uncaught edge cases: Mitigated by full E2E test suite covering Path A middleware, Path B script ingest, queue processing, and dashboard presentation.
</threat_model>

<tasks>

<task id="01-e2e-verification-test" autonomous="true">
  <action>Create EndToEndVerificationTest suite</action>
  <description>Create `tests/Feature/EndToEndVerificationTest.php`. Test full tracking flow: site registration -> pageview submission to `POST /api/collect` -> queue job dispatch & worker execution (`queue:work --once`) -> `events` table verification -> `AnalyticsService` accuracy check -> `/dashboard` Inertia page response check.</description>
  <read_first>tests/Feature/CollectEndpointTest.php</read_first>
  <requirements>All v1 requirements</requirements>
  <acceptance_criteria>`EndToEndVerificationTest` passes completely (`php artisan test --filter=EndToEndVerificationTest`).</acceptance_criteria>
</task>

<task id="02-production-readme-and-docs" autonomous="true">
  <action>Create production README documentation</action>
  <description>Create root `README.md`. Document project core value, architecture (Monorepo with `packages/lumina-core`), Docker Compose quickstart for self-hosting, Laravel Cloud deployment guide, Embedded Package setup guide (`lumina/core` middleware & `@livewire('lumina-dashboard')`), configuration options, and testing instructions.</description>
  <read_first>project-en.md</read_first>
  <requirements>DEPLOY-01, DEPLOY-02</requirements>
  <acceptance_criteria>`README.md` exists and covers all deployment & package usage instructions cleanly.</acceptance_criteria>
</task>

<task id="03-final-test-suite-execution" autonomous="true">
  <action>Run and verify full test suite</action>
  <description>Execute full test suite across all 10 phases using `php artisan test` and `vendor/bin/pest packages/lumina-core/tests/`. Ensure zero test failures.</description>
  <read_first>tests/Feature/EndToEndVerificationTest.php</read_first>
  <requirements>All v1 requirements</requirements>
  <acceptance_criteria>Full test suite passes with 0 failures.</acceptance_criteria>
</task>

</tasks>

## Artifacts this phase produces
- Test: `tests/Feature/EndToEndVerificationTest.php`
- Documentation: `README.md`

## Verification Criteria
- `php artisan test --filter=EndToEndVerificationTest` passes.
- `php artisan test` passes with 0 failures across all 10 phases.
- `README.md` provides complete setup guides for Docker Compose, Laravel Cloud, and Embedded package mode.

## must_haves
- All 34 MVP requirements must be verified and passing.
- Working tree clean and free of unresolved issues.
