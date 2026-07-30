# Lumina 📊

> **Lightweight, self-hosted web analytics for Laravel.**  
> Plausible-class privacy-first web analytics built natively into the Laravel ecosystem without adding extra infrastructure runtimes.

---

## 🌟 Core Architecture

Lumina is architected as a **monorepo**:
- **`packages/lumina-core`**: High-performance core package containing models (`Site`, `Event`), migrations, visitor hashing (`sha256(IP + UserAgent + dailySalt)`), server-side tracking middleware (Path A), public script ingest controller (Path B), `AnalyticsService` query engine with 60s caching, and the embedded `lumina-dashboard` Livewire component.
- **Standalone App**: Modern Vue 3 + Inertia.js web dashboard with multi-site switcher, date range filters, interactive daily trend charts, top pages/referrers, and custom events.

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

### 4. Client-Side Tracking Snippet (Path B)
Include the non-blocking vanilla JS script tag (< 2KB):
```html
<script defer data-domain="yourdomain.com" src="https://your-lumina.com/js/script.js"></script>
```

#### Custom Events API
```js
window.lumina('checkout_completed', { plan: 'pro', price: 29.99 });
```

### 5. Render Embedded Livewire Dashboard Component
In any Blade view in your host application:
```blade
<livewire:lumina-dashboard :site="$site" />
```

---

## 🧪 Testing & Verification

Run the comprehensive Pest test suite across all 10 phases:

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
