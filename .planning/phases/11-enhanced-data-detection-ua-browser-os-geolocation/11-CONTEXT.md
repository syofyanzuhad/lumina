# Phase 11 Context: Enhanced Data Detection (UA Browser/OS & Geolocation)

## Domain
Parse User-Agent for detailed Browser & OS versioning. Geolocation IP detection (country code & country name). Aggregate queries for Browsers, Operating Systems, Countries in `AnalyticsService`. Render Browser, OS, and Location cards in Vue & Livewire dashboards.

## Canonical References
- `PROJECT.md`
- `REQUIREMENTS.md`

## Decisions
- **UA Parsing Approach**: Use `jenssegers/agent` (widely used, robust, standard Laravel choice).
- **GeoIP Detection Method**: Check for Cloudflare headers (`CF-IPCountry`) first, then fallback to an external API like `ip-api` if missing.
- **Dashboard UI Display Format**: Simple Top-N lists (e.g. Top 5) with percentage progress bars for clean and fast implementation in both Vue and Livewire.

## Deferred Ideas
(None)
