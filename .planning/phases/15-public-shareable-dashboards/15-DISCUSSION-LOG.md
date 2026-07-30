# Phase 15 Discussion Log: Public & Shareable Dashboards

## Date: 2026-07-31

## Areas Discussed & Decisions

### 1. Share Link Generation & Access Control
- **Options Considered**: Custom Slugs vs Hashed Tokens vs High-Entropy Random Tokens
- **Selected**: High-entropy 32-char token / UUID (`share_token`) with `is_public` toggle on `sites` model.

### 2. Password & Access Restrictions
- **Options Considered**: No Password vs Password Protection vs Referrer Domain Check
- **Selected**: Optional password protection only using `share_password` column and session verification.

### 3. Public Dashboard View Capabilities
- **Options Considered**: Minimal Summary vs Full Read-Only Parity
- **Selected**: Full read-only dashboard parity with administration and export controls hidden.

## Deferred Ideas
- Custom subdomains / vanity URLs (`/share/my-custom-slug`) — deferred to v2.0.
