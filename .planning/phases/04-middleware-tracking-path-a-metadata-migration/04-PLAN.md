---
wave: 1
depends_on: []
files_modified:
  - packages/lumina-core/src/LuminaCoreServiceProvider.php
  - packages/lumina-core/src/Enums/DeviceType.php
  - packages/lumina-core/src/Models/Event.php
  - routes/web.php
autonomous: true
---

# Phase 4 Plan: Middleware Tracking & Metadata Migration

<threat_model>
- Data spoofing (submitting false analytics): Mitigated by rate limiting and strict site validation.
- Privacy violation (tracking raw user IP): Prevented by one-way daily salt visitor hashing.
- Denial of Service (DoS): Rate limiters drop excessive requests per IP and per site with minimal overhead.
</threat_model>

<tasks>

<task id="01-metadata-migration-and-model" autonomous="true">
  <action>Create additive migration for metadata and update Event model</action>
  <description>Generate a new migration `packages/lumina-core/database/migrations/2026_07_30_000001_add_metadata_to_events_table.php` adding a nullable json `metadata` column. Add `'metadata' => 'array'` to `Event` casts.</description>
  <read_first>packages/lumina-core/src/Models/Event.php</read_first>
  <requirements>ARCH-02</requirements>
  <acceptance_criteria>Migration exists and is compatible with Postgres/MySQL. Model casts metadata array correctly.</acceptance_criteria>
</task>

<task id="02-device-type-enum" autonomous="true">
  <action>Add fromUserAgent method to DeviceType Enum</action>
  <description>Add a `public static function fromUserAgent(string $userAgent): self` to `DeviceType` that parses the UA via regex into Mobile, Tablet, or Desktop, falling back to Unknown.</description>
  <read_first>packages/lumina-core/src/Enums/DeviceType.php</read_first>
  <requirements>ARCH-02</requirements>
  <acceptance_criteria>Method accurately matches tablet, mobile, and desktop patterns without external dependencies.</acceptance_criteria>
</task>

<task id="03-insert-event-job" autonomous="true">
  <action>Create InsertEvent queued job</action>
  <description>Create `packages/lumina-core/src/Jobs/InsertEvent.php` implementing `ShouldQueue`. Accept primitives (siteId, path, referrer, visitorHash, deviceType, country, metadata) in constructor and call `Event::create()` in `handle()`.</description>
  <read_first>packages/lumina-core/src/Jobs/InsertEvent.php (will create new, refer to research)</read_first>
  <requirements>INGEST-03, QUEUE-01</requirements>
  <acceptance_criteria>Class implements ShouldQueue, receives primitive properties, and inserts Event.</acceptance_criteria>
</task>

<task id="04-service-provider" autonomous="true">
  <action>Update LuminaCoreServiceProvider for rate limiters and middleware</action>
  <description>Update `boot()` in `packages/lumina-core/src/LuminaCoreServiceProvider.php` to define `lumina_ip` and `lumina_site` rate limiters, and alias `lumina.track` to `TrackPageview::class`.</description>
  <read_first>packages/lumina-core/src/LuminaCoreServiceProvider.php</read_first>
  <requirements>INGEST-05</requirements>
  <acceptance_criteria>Router has `lumina.track` alias registered. Rate limiters are defined using `RateLimiter::for()`.</acceptance_criteria>
</task>

<task id="05-track-pageview-middleware" autonomous="true">
  <action>Create TrackPageview middleware</action>
  <description>Create `packages/lumina-core/src/Middleware/TrackPageview.php`. Resolve site by request host, return next immediately if unregistered or rate limited. Hash IP+UA using daily salt. Parse DeviceType from UA. Extract country from X-Country header. Dispatch InsertEvent and return response.</description>
  <read_first>packages/lumina-core/src/Middleware/TrackPageview.php (new)</read_first>
  <requirements>PRIV-01, PRIV-02, PRIV-03, PRIV-04, SITE-05, INGEST-01, INGEST-02, INGEST-04</requirements>
  <acceptance_criteria>Middleware handles rate limit silently (no 429). Missing host bypasses tracking gracefully. Dispatch uses parsed variables. Raw IP is not stored.</acceptance_criteria>
</task>

<task id="06-wiring-and-testing" autonomous="true">
  <action>Wire middleware to web routes and add Feature test</action>
  <description>Update `routes/web.php` to include `lumina.track` middleware. Create `tests/Feature/TrackPageviewMiddlewareTest.php` asserting job dispatch, database insertion, silent rate limit fallback, privacy hashing, and invalid host bypass.</description>
  <read_first>routes/web.php</read_first>
  <requirements>ARCH-02, PRIV-01</requirements>
  <acceptance_criteria>Web routes use `lumina.track`. Test suite passes successfully.</acceptance_criteria>
</task>

</tasks>

## Artifacts this phase produces
- Migration file: `packages/lumina-core/database/migrations/2026_07_30_000001_add_metadata_to_events_table.php`
- Class: `Lumina\Core\Jobs\InsertEvent`
- Class: `Lumina\Core\Middleware\TrackPageview`
- Function: `DeviceType::fromUserAgent()`
- Test: `tests/Feature/TrackPageviewMiddlewareTest.php`

## Verification Criteria
- Events must be queued to database upon a valid tracked web request.
- Unregistered domains will be ignored by middleware.
- Raw IPs must not be stored in `visitor_hash`.
- The rate limiter must silently swallow requests, avoiding 429s on host web routes.

## must_haves
- All requirements mentioned must be addressed.
- The `metadata` column MUST be nullable to preserve existing tables.
- Daily salt must rotate based on date.
- `InsertEvent` job must receive primitive data, not Models.
