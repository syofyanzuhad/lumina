---
wave: 1
depends_on: []
files_modified:
  - packages/lumina-core/src/Models/Site.php
  - packages/lumina-core/database/factories/SiteFactory.php
  - app/Http/Controllers/ShareController.php
  - routes/web.php
autonomous: true
---

# Phase 15: Public & Shareable Dashboards (Backend)

## requirements
- REQ-SHARE-01
- REQ-SHARE-02
- REQ-SHARE-03

## must_haves
```yaml
truths:
  - "The sites table is extended with is_public, share_token, and share_password."
  - "Site model exposes helpers hasSharePassword, isPubliclyAccessible, generateShareToken."
  - { statement: "Share route 404s if site is not public or token is invalid.", verification: backstop }
prohibitions:
  - statement: "Public /share/{token} view routes MUST NOT require auth middleware, whereas site management routes MUST be authenticated."
```

## tasks

```xml
<task>
  <id>1</id>
  <name>Migration and Site Model Update</name>
  <read_first>
    <file>packages/lumina-core/src/Models/Site.php</file>
    <file>packages/lumina-core/database/factories/SiteFactory.php</file>
  </read_first>
  <action>
    Create a database migration in packages/lumina-core/database/migrations to add 'is_public' (boolean, default false), 'share_token' (string, length 32, nullable, unique, index), and 'share_password' (string, nullable) to the 'sites' table.
    Update the Site model: add the 3 columns to the fillable array, add 'share_password' to the $hidden array, cast 'is_public' to boolean, and implement three helper methods: 'hasSharePassword(): bool', 'isPubliclyAccessible(): bool', and 'generateShareToken(): string' (using Str::random(32)).
    Update SiteFactory to include state methods 'public()' (sets is_public to true, share_token to random 32 char string) and 'passwordProtected()' (sets is_public to true, share_token, share_password to Hash::make('secret')).
  </action>
  <acceptance_criteria>
    Migration runs successfully without errors.
    Site::factory()->public()->create() creates a site with a share token.
    Site model instances have the new helper methods.
  </acceptance_criteria>
</task>

<task>
  <id>2</id>
  <name>ShareController and Routing</name>
  <read_first>
    <file>routes/web.php</file>
  </read_first>
  <action>
    Create app/Http/Controllers/ShareController.php. Implement 'show' (fetches Site by share_token where is_public is true, returns 404 otherwise, checks session for password if required, injects AnalyticsService to retrieve metrics like getOverview, date range, custom events, and passes $overview, $period, $activeTab, etc. to Inertia::render('Share/Show')). Implement 'authenticate' (verifies password and sets session("share_auth_{$site->id}") to true). Implement 'update' (MUST authorize site ownership via `$this->authorize('update', $site)` or `can:update,site` middleware, then save is_public and share_password for authenticated owners). Implement 'regenerate' (MUST authorize site ownership, generates a new share_token and saves).
    In routes/web.php, add public routes: GET /share/{token} mapped to ShareController@show, POST /share/{token}/password mapped to ShareController@authenticate under the 'lumina.track' middleware.
    Add authenticated management routes: PUT /sites/{site}/share mapped to ShareController@update, POST /sites/{site}/share/regenerate mapped to ShareController@regenerate under the 'auth', 'verified', 'lumina.track' middleware group.
  </action>
  <acceptance_criteria>
    php artisan route:list includes sites.share.show, sites.share.authenticate, sites.share.update, sites.share.regenerate.
    ShareController exists with the 4 methods defined.
  </acceptance_criteria>
</task>
```

## Artifacts this phase produces
- Migration file for sites table share columns
- ShareController class
- sites.share.show route
- sites.share.authenticate route
- sites.share.update route
- sites.share.regenerate route
- Site::hasSharePassword method
- Site::isPubliclyAccessible method
- Site::generateShareToken method
- SiteFactory::public method
- SiteFactory::passwordProtected method
