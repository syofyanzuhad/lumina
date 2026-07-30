# Phase 15 Research: Public & Shareable Dashboards

## Key Findings
1. **Architecture & Location**: Lumina follows a hybrid architecture where models (`Site`, `Event`, `Goal`), database migrations, `AnalyticsService`, and core Livewire components (`lumina-dashboard`) reside in `packages/lumina-core`. Controllers, Inertia pages, and web routes reside in the main application (`app/Http/Controllers`, `resources/js/Pages`, `routes/web.php`).
2. **Schema Requirements**: Extending `sites` table requires a package migration adding `is_public` (boolean, default `false`), `share_token` (string 32, nullable, indexed, unique), and `share_password` (string, nullable bcrypt hash).
3. **Public Route & Security**: Public route `GET /share/{token}` must resolve site strictly where `is_public = true` and `share_token = $token`. If no matching site exists or `is_public` is false, it returns `404 Not Found` to prevent token enumeration.
4. **Session-Based Password Auth**: When `share_password` is set on a site, unauthenticated visitors are shown a password prompt. Upon entering the correct password, a session flag (`session(["share_auth_{$site->id}" => true])`) is set, granting access to the read-only dashboard.
5. **Read-Only Visual Parity**: The public dashboard UI (`resources/js/Pages/Share/Show.vue`) must render full analytics metrics while strictly hiding all management controls.
6. **Data Reuse**: `AnalyticsService` methods power both Inertia Vue public dashboard and embedded Livewire `lumina-dashboard` without needing modified queries.

---

## Site Model & Schema

### Existing Schema & Model (`packages/lumina-core/src/Models/Site.php`)
- **Table**: `sites`
- **Columns**: `id`, `domain` (unique string), `owner_id` (FK to users), `created_at`, `updated_at`.
- **Fillable**: `['domain', 'owner_id']`

### Migration Plan
- **File**: `packages/lumina-core/database/migrations/2026_07_31_000000_add_share_columns_to_sites_table.php`
- **Columns to Add**:
  - `is_public`: `boolean('is_public')->default(false)`
  - `share_token`: `string('share_token', 32)->nullable()->unique()->index()`
  - `share_password`: `string('share_password')->nullable()`

### Site Model Updates
- **Fillable**: Add `'is_public'`, `'share_token'`, `'share_password'`
- **Casts**: Add `'is_public' => 'boolean'`
- **Helper Methods**:
  - `hasSharePassword(): bool`
  - `isPubliclyAccessible(): bool`
  - `generateShareToken(): string`

### Factory Updates (`packages/lumina-core/database/factories/SiteFactory.php`)
- Add state methods:
  - `public(?string $token = null)`: sets `is_public => true`, `share_token => $token ?? Str::random(32)`
  - `passwordProtected(string $password = 'secret')`: sets `is_public => true`, `share_token`, `share_password => Hash::make($password)`

---

## Existing Route Structure

- **Unauthenticated/Public**: `GET /` and `GET /demo` with `middleware('lumina.track')`
- **Authenticated Group**: `Route::middleware(['auth', 'verified', 'lumina.track'])` containing `/dashboard`, `/sites`, `/sites/{site}`, `/sites/{site}/export`, `/sites/{site}/goals`

### Recommended Route Additions

```php
// Public Share Routes (Unauthenticated)
Route::middleware(['lumina.track'])->group(function () {
    Route::get('/share/{token}', [ShareController::class, 'show'])->name('sites.share.show');
    Route::post('/share/{token}/password', [ShareController::class, 'authenticate'])->name('sites.share.authenticate');
});

// Authenticated Share Management Routes
Route::middleware(['auth', 'verified', 'lumina.track'])->group(function () {
    Route::put('/sites/{site}/share', [ShareController::class, 'update'])->name('sites.share.update');
    Route::post('/sites/{site}/share/regenerate', [ShareController::class, 'regenerate'])->name('sites.share.regenerate');
});
```

---

## Dashboard Vue Architecture

### Existing Dashboard (`resources/js/Pages/Dashboard.vue`)
- Site selection header, period selector, tab navigation, KPI cards, SVG timeseries chart, top pages/referrers, device/browser/OS/country breakdowns, custom events, goals
- Admin-only elements: Site dropdown selector, Site Settings link, Export dropdown, Live refresh toggle

### Public Dashboard (`resources/js/Pages/Share/Show.vue`)
- **Props**: `site`, `period`, `activeTab`, `overview`, `requiresPassword`, `passwordError`, custom event metrics
- **Mode 1**: Password gate screen when `requiresPassword === true`
- **Mode 2**: Full read-only dashboard with admin controls hidden
- Uses minimal `PublicLayout` (no app nav sidebar), shows "Powered by Lumina" badge

---

## Livewire Dashboard Architecture

### Existing Component (`packages/lumina-core/src/Livewire/Dashboard.php`)
- Alias: `lumina-dashboard`
- Accepts `Site $site` prop, handles `$period`, `$activeTab`, `$selectedEvent` state
- `lumina-dashboard` is natively read-only (no admin settings/export buttons)
- A public Blade view `resources/views/share.blade.php` can host `<livewire:lumina-dashboard :site="$site" />` for Blade/Livewire parity

---

## SiteController Patterns

### Share Settings Integration into `Sites/Show.vue`
- Add "Public Sharing & Security" section card:
  - Toggle: Enable/Disable Public Sharing (`is_public`)
  - Share URL field with copy-to-clipboard
  - Regenerate button (`POST /sites/{site}/share/regenerate`)
  - Password protection input (set or clear `share_password`)

---

## AnalyticsService Methods

- `getOverview(Site $site, CarbonInterface $start, CarbonInterface $end): array`
  - Returns: `total_pageviews`, `unique_visitors`, `top_pages`, `top_referrers`, `daily_pageviews`, `device_breakdown`, `top_browsers`, `top_os`, `top_countries`, `custom_events`, `goals`
- `getCustomEventSummary()`, `getCustomEventsList()`, `getCustomEventTimeline()`, `getCustomEventPropertyKeys()`, `getCustomEventPropertyBreakdown()`, `getCustomEventLogs()`
- **Caching**: 60-second cache by site ID and date range

---

## Test Patterns

- Pest PHP tests in `tests/Feature/`
- Pattern from `DashboardControllerTest.php` and `ExportControllerTest.php`:
  - `use RefreshDatabase;`
  - `User::factory()->create()` & `Site::factory()->create()`
  - `actingAs($user)` for authenticated actions
  - `assertInertia()` for Inertia response assertions

---

## Recommended Implementation Approach (File-by-File)

1. **Migration**: `packages/lumina-core/database/migrations/2026_07_31_000000_add_share_columns_to_sites_table.php`
2. **Model**: `packages/lumina-core/src/Models/Site.php` — fillables, casts, helper methods
3. **Factory**: `packages/lumina-core/database/factories/SiteFactory.php` — `public()` and `passwordProtected()` states
4. **ShareController**: `app/Http/Controllers/ShareController.php` — `show`, `authenticate`, `update`, `regenerate`
5. **Routes**: `routes/web.php` — public and authenticated share routes
6. **Site Settings UI**: `resources/js/Pages/Sites/Show.vue` — add share management card
7. **Public Inertia Page**: `resources/js/Pages/Share/Show.vue` — read-only dashboard + password gate
8. **Blade View** (optional parity): `resources/views/share.blade.php` — `lumina-dashboard` component
9. **Tests**: `tests/Feature/ShareControllerTest.php`

---

## Validation Architecture

### Testing Strategy
1. `GET /share/{token}` returns `200 OK` + Inertia `Share/Show` when `is_public = true`
2. `GET /share/{token}` returns `404` when `is_public = false`
3. `GET /share/invalid-token` returns `404`
4. Password-protected site returns `requiresPassword: true`
5. `POST /share/{token}/password` with correct password sets session flag and redirects to dashboard
6. `POST /share/{token}/password` with wrong password returns to password gate
7. Token regeneration invalidates old link and activates new one
8. Public visitors cannot access site settings, goal endpoints, or export streams
