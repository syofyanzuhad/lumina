# Lumina 📊

> **Lightweight, self-hosted web analytics for Laravel.**
> Plausible-class privacy-first web analytics built natively into the Laravel ecosystem without adding extra infrastructure runtimes.

---

## 📸 Screenshots

<img width="2880" height="3878" alt="uselumina_laravel_cloud_demo" src="https://github.com/user-attachments/assets/df45529b-4943-4b75-a678-7464738d5c93" />
<!-- <img width="2880" height="3856" alt="lumina test_demo" src="https://github.com/user-attachments/assets/09f745ab-5bca-4017-bf93-3014bc58491d" /> -->

---

## 🌟 Key Features

- **Privacy-First & Cookie-Free**: 100% cookie-free operating mode — no consent banner required. Visitors are identified by opaque random IDs kept in `localStorage`/`sessionStorage` (never cookies), and raw IPs are never stored. When client-side IDs are unavailable, an irreversible stable-salt hash (`sha256(IP + UserAgent + salt)`) preserves cross-day uniques without storing personal data.
- **Dual Tracking Paths**: server-side middleware tracking (Path A) for guaranteed coverage of every request — including bots, crawlers, and visitors with JavaScript disabled — and a lightweight client-side JS script (Path B, ~2.8KB raw / ~1.25KB gzipped) for richer browser-side signals like outbound-click tracking and custom events. See [Choosing a tracking path](#-choosing-a-tracking-path) below.
- **Session-Based Analytics**: bounce rate and average visit duration are computed from real 30-minute sessions (client-generated `session_id`, refreshed on inactivity), not approximated from daily aggregates.
- **Zero-Latency Tracking**: server-side tracking runs in terminable middleware *after* the response is sent, with atomic per-IP/per-site rate limiting and cached site lookups — no impact on page speed.
- **Automatic Outbound Link Tracking**: the client script detects clicks on links pointing to a different hostname and fires an `Outbound Link: Click` event automatically — no extra setup required.
- **SPA & Inertia-Aware Navigation**: the tracker patches `history.pushState`/`replaceState`, listens for `popstate`, and explicitly listens for the `inertia:navigate` event, so route changes in Inertia-driven apps are tracked reliably without duplicate or missed pageviews.
- **Enhanced Data Detection**: automatic User-Agent resolution (Browser & Operating System) and GeoIP country resolution, with a trusted-proxy boundary for edge country headers.
- **Custom Event & Goal Tracking**: track custom JavaScript events and set up conversion goals based on paths or events with real-time conversion rates.
- **Shareable & Public Dashboards**: easily share dashboard access via share links with optional password protection.
- **Streaming Data Exports**: export raw pageviews, custom events, and summary data directly as CSV or JSON.
- **Monorepo Architecture**: includes `packages/lumina-core` for embedded analytics in host Laravel apps, as well as a standalone Vue 3 + Inertia.js web application.

---

## 🚀 Quickstart & Deployment

### Recommended: Laravel Cloud

Lumina is designed for [Laravel Cloud](https://cloud.laravel.com/) — zero-config queues, scheduling, and managed Postgres/MySQL out of the box.

> **Requirements:** Laravel Cloud requires Laravel 11+ and PHP 8.2+ (Lumina currently runs on Laravel 13 / PHP 8.4).
>
> **Pricing Estimate:** The minimum **Starter plan** is **$5/month**. This includes $5 in monthly usage credits and "scale-to-zero" hibernation. For most small, low-traffic, or hobby installations of Lumina, these included credits will cover your database and compute usage, keeping your effective cost at just the $5 base.

1. Fork this repository or push the code to your own GitHub account.
2. Connect it on [cloud.laravel.com](https://cloud.laravel.com/) — Cloud auto-detects the Laravel app, queues, and scheduler.
3. Point a custom domain or use the built-in `.laravel.cloud` URL.
4. Open the dashboard — you're live.

> **Tip:** Set `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`, and `CACHE_STORE=database` in your Cloud environment if not already default.

### Alternative: Self-Hosted (Docker Compose)

```bash
git clone https://github.com/syofyanzuhad/lumina.git && cd lumina
cp .env.docker.example .env && php artisan key:generate
docker compose up -d --build
```

Access Lumina at `http://localhost:8080`. A Supervisor queue worker and cron scheduler are included in the Docker image — no extra setup needed.

---

## 🖥️ Dashboard Surfaces (canonical vs. embedded)

The **standalone Vue 3 + Inertia SPA dashboard** (`resources/js/pages/Dashboard.vue`) is the **canonical product surface** — it is where new analytics features ship first and where metric logic is defined (`AnalyticsService`, aggregated in SQL with tagged caching).

The embedded package (`packages/lumina-core`) additionally ships a Livewire dashboard and a Filament plugin for host apps. These are **thin renderers over `AnalyticsService` only** — they never re-implement metric logic. When a metric changes, update `AnalyticsService` once; all surfaces inherit it.

---

## 📦 Embedded Package Mode (`lumina/core`)

Install `lumina/core` directly into any host Laravel application to get embedded analytics inside your own app's layout.

### 1. Require Package

```bash
composer require lumina/core
```

### 2. Publish Migrations

```bash
php artisan vendor:publish --tag=lumina-core-migrations
php artisan migrate
```

### 3. Choosing a Tracking Path

Lumina ships two independent tracking paths. They are **not mutually exclusive** — most production sites should use both together, since each covers a gap the other leaves open.

| | Path A: Server-side middleware | Path B: Client-side JS script |
|---|---|---|
| Coverage | Every HTTP request that hits the route, including bots, crawlers, and visitors with JavaScript disabled or blocked | Only visitors who successfully load and execute the script |
| Signals collected | Path, referrer (from headers), User-Agent, IP-derived country | Path, referrer, screen width, outbound link clicks, custom events |
| Performance impact | None — runs in terminable middleware after the response is already sent | Negligible — deferred `<script>`, ~1.25KB gzipped over the wire |
| Best for | Guaranteed baseline pageview counts, no-JS/bot-heavy traffic | Richer client behavior: outbound clicks, custom events, screen data |

**If you only install Path B**, any visitor with JavaScript disabled, blocked, or failing to load will be invisible to your analytics — there is no automatic `<noscript>` fallback bundled with the script itself. For accurate baseline traffic counts, attach the `TrackPageview` middleware to your routes as well.

#### Path A: Server-Side Middleware Tracking

```php
use Lumina\Core\Middleware\TrackPageview;

Route::middleware([TrackPageview::class])->group(function () {
    Route::get('/', [HomeController::class, 'index']);
});
```

#### Path B: Client-Side Tracking Snippet & Custom Events

Include the non-blocking vanilla JS script tag:

```html
<script defer data-domain="yourdomain.com" src="https://your-lumina.com/js/script.js"></script>
```

Optional attributes:

```html
<script
  defer
  data-domain="yourdomain.com"
  data-api="https://your-lumina.com/api/collect"
  data-exclude="/admin/*,/internal-tools"
  src="https://your-lumina.com/js/script.js"
></script>
```

- `data-exclude` — comma-separated list of paths (wildcard `*` supported) to skip tracking on.
- Visitors can opt out locally at any time by running `localStorage.setItem('lumina_ignore', 'true')` in their browser console.

#### Custom Events API

```js
// Track custom event with optional metadata properties
window.lumina('checkout_completed', { plan: 'pro', price: 29.99 });
```

Outbound link clicks (links to a different hostname) are tracked automatically as an `Outbound Link: Click` event — no extra code required.

---

## 📊 Features & API Usage

### 1. Goal Conversion Tracking

Create and manage conversion goals via REST endpoints:

```php
// POST /sites/{site}/goals
[
    'name' => 'Signups',
    'target_type' => 'custom_event', // 'path' or 'custom_event'
    'target_value' => 'signup_completed',
]
```

### 2. Streaming Data Export Endpoints

Fetch streamed CSV or JSON exports directly:

- `GET /sites/{site}/export?type=events&format=csv`
- `GET /sites/{site}/export?type=pageviews&format=json`
- `GET /sites/{site}/export?type=summary&format=csv`

### 3. Public & Shareable Dashboards

Configure public dashboard access and optional password protection:

```php
// PUT /sites/{site}/share
[
    'is_public' => true,
    'share_password' => 'optional-secret-passphrase',
]
```

Access public share links at `/share/{token}` (with password challenge if enabled).

---

## 🧪 Testing

```bash
# Run full application test suite
php artisan test

# Run package-core tests
vendor/bin/pest packages/lumina-core/tests/

# Run frontend component/composable/tracker tests (Vitest)
npm run test:frontend

# Full local gate — lint, format, types, frontend tests, PHP tests, PHPStan
composer ci:check
```

---

## 🛡️ Privacy & Compliance

- **No Cookies**: Lumina operates 100% cookie-free — no GDPR/CCPA/PECR consent banner needed.
- **No Fingerprinting**: opaque random visitor/session IDs (`localStorage`/`sessionStorage`) carry no personal or device-identifying data and are never shared cross-site.
- **Zero Raw IP Storage**: IP addresses are never saved directly to the database. When client-side IDs are unavailable, an irreversible stable-salt hash (`hash('sha256', IP + UserAgent + salt)`) is used instead — the stable salt keeps cross-day unique visitors exact without ever storing raw IPs.
- **Trusted-Proxy Boundary**: edge-proxy country headers (`CF-IPCountry`, `X-Vercel-IP-Country`) are only honored from configured trusted proxies, so spoofable headers are never silently accepted.
- **Minimal Payload**: the client script only ever sends `domain`, `path`, `referrer`, `screen_width`, event `name`, and event `metadata` — no timezone, language, or full device fingerprint is collected.

> **Note on visitor identity:** since visitor/session IDs live in `localStorage`/`sessionStorage` rather than cookies, a visitor who clears site data, switches browsers, or uses private browsing will be counted as a new visitor on their next visit. This is a deliberate privacy trade-off (the same one made by comparable tools such as Plausible and Umami), not a defect — but it means "unique visitor" counts should be read as *unique browser sessions*, not *unique people*.

---

## 📄 License

MIT License. Built for the Laravel Community.
---
