# Lumina 📊

> **Lightweight, self-hosted web analytics for Laravel.**  
> Plausible-class privacy-first web analytics built natively into the Laravel ecosystem without adding extra infrastructure runtimes.

---

## 📸 Screenshots

<img width="2880" height="3856" alt="lumina test_demo" src="https://github.com/user-attachments/assets/09f745ab-5bca-4017-bf93-3014bc58491d" />

---

## 🌟 Key Features

- **Privacy-First & Cookie-Free**: 100% cookie-free operating mode. IP addresses are hashed using irreversible daily salts (`sha256(IP + UserAgent + dailySalt)`). No persistent cross-site tracking or fingerprinting.
- **Flexible Tracking**: Supports both server-side middleware tracking (Path A) and lightweight client-side JS script tracking (< 2KB, Path B).
- **Enhanced Data Detection**: Automatic User-Agent resolution (Browser & Operating System) and GeoIP country resolution.
- **Custom Event & Goal Tracking**: Track custom JavaScript events and set up conversion goals based on paths or events with real-time conversion rates.
- **Shareable & Public Dashboards**: Easily share dashboard access via share links with optional password protection.
- **Streaming Data Exports**: Export raw pageviews, custom events, and summary data directly as CSV or JSON.
- **Monorepo Architecture**: Includes `packages/lumina-core` for embedded analytics in host Laravel apps, as well as a standalone Vue 3 + Inertia.js web application.

---

## 🚀 Quickstart & Deployment

### Option A: Self-Hosted Docker Compose (VPS Deploy)

1. Clone the repository:
   ```bash
   git clone https://github.com/syofyanzuhad/lumina.git
   cd lumina
   ```

2. Configure environment variables:
   ```bash
   cp .env.docker.example .env
   php artisan key:generate
   ```

3. Start services via Docker Compose:
   ```bash
   docker compose up -d --build
   ```

4. Access Lumina at `http://localhost:8080` (or your configured `$PORT`).

### Option B: Laravel Cloud / Traditional VPS

1. Configure `.env`:
   ```env
   QUEUE_CONNECTION=database
   SESSION_DRIVER=database
   CACHE_STORE=database
   ```

2. Run persistent queue worker under Supervisor:
   ```ini
   [program:lumina-worker]
   command=php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   stopwaitsecs=360
   ```

---

## 📦 Embedded Package Mode (`lumina/core`)

Install `lumina/core` directly into any host Laravel application to get embedded analytics inside your own app's layout!

### 1. Require Package
```bash
composer require lumina/core
```

### 2. Publish Migrations
```bash
php artisan vendor:publish --tag=lumina-core-migrations
php artisan migrate
```

### 3. Server-Side Middleware Tracking (Path A)
In `bootstrap/app.php` or `routes/web.php`:
```php
use Lumina\Core\Middleware\TrackPageview;

Route::middleware([TrackPageview::class])->group(function () {
    Route::get('/', [HomeController::class, 'index']);
});
```

### 4. Client-Side Tracking Snippet & Custom Events (Path B)
Include the non-blocking vanilla JS script tag (< 2KB):
```html
<script defer data-domain="yourdomain.com" src="https://your-lumina.com/js/script.js"></script>
```

#### Custom Events API
```js
// Track custom event with optional metadata properties
window.lumina('checkout_completed', { plan: 'pro', price: 29.99 });
```

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

Run the test suite:

```bash
# Run full application test suite
php artisan test

# Run package-core tests
vendor/bin/pest packages/lumina-core/tests/
```

---

## 🛡️ Privacy & Compliance

- **No Cookies**: Lumina operates 100% cookie-free.
- **No Fingerprinting**: No persistent browser fingerprinting or cross-site tracking.
- **Zero Raw IP Storage**: IP addresses are never saved directly to the database. Visitor uniqueness uses an irreversible daily salt hash (`hash('sha256', IP + UserAgent + dailySalt)`).

---

## 📄 License

MIT License. Built for the Laravel Community.

