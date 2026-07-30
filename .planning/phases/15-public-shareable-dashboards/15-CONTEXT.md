# Phase 15 Context: Public & Shareable Dashboards

## Domain
Enable site owners to generate public shareable links for their site analytics dashboards (`/share/{token}`). Support public toggle, high-entropy share tokens, optional password protection, and read-only UI rendering across Inertia/Vue and Livewire.

## Canonical References
- `PROJECT.md`
- `REQUIREMENTS.md` (REQ-SHARE-01, REQ-SHARE-02, REQ-SHARE-03)

## Decisions

### 1. Share Link Generation & Access Control
- **Database Schema**: Add `is_public` (boolean, default false), `share_token` (string, nullable, indexed, 32-char high-entropy string/UUID), and `share_password` (string, nullable) to `sites` table.
- **Route & Route Parameter**: `GET /share/{token}` resolves site by `share_token` where `is_public = true`.
- **Token Lifecycle**: Generating or regenerating a share token invalidates old links instantly. Disabling public sharing sets `is_public = false`.

### 2. Password Protection & Security
- **Authentication Check**: If `share_password` is set, prompt visitor with a password form before showing analytics.
- **Session Verification**: Store verified session state (`session(["share_auth_{$site->id}" => true])`) upon correct password submission.
- **Security & Scope**: Share routes are strictly read-only; no site settings, management endpoints, or export actions are exposed to unauthenticated public visitors.

### 3. Public Dashboard UI & Read-Only Parity
- **Read-Only Rendering**: Render the full analytics dashboard (Overview, Custom Events, Goals, Breakdown cards) but hide interactive administration controls.
- **Hidden Controls**: Hide site selector dropdown, site settings link, export dropdown menu, live refresh controls, and navigation sidebars.
- **Header Display**: Display a clean public header featuring the site domain, active date range selector, and a "Powered by Lumina" badge.
- **Parity**: Both Vue (`Pages/Share/Show.vue`) and embedded Livewire public views share full visual and metric parity.

## Deferred Ideas
- Custom subdomains or vanity URLs (`acme.analytics.example.com`) — deferred to v2.0.
- Domain referrer restrictions for iframe embeds — deferred to future release.
