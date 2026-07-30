# Phase 6: Queue Worker & Deployment Config — Research

**Gathered:** 2026-07-30
**Phase:** 06 — Queue Worker & Deployment Config
**Status:** Completed

---

## 1. Domain Architecture & Boundaries

Phase 6 provides production-ready deployment configurations and verifies queue execution:
1. **Queue Configuration**:
   - `config/queue.php` defaults to `QUEUE_CONNECTION=database`.
   - `jobs` table migration was installed in Phase 1 / Phase 3.
   - Supervisor config `deployment/supervisor/lumina-worker.conf` manages `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600`.

2. **Dockerization Architecture**:
   - Production multi-stage `Dockerfile`:
     - Base: `php:8.3-fpm-alpine` or `debian` base with Nginx + Supervisor + PHP extensions (`pdo_pgsql`, `pdo_mysql`, `gd`, `zip`, `opcache`).
     - Asset stage: Node.js build for Vite frontend assets & tracker script (`public/js/script.js`).
     - Production stage: Clean PHP-FPM + Nginx + Supervisor image running both Nginx/FPM and `queue:work` under Supervisor.
   - `docker-compose.yml`:
     - Service `app`: Lumina application running on port 8080 or 80.
     - Service `db`: PostgreSQL 16 image with volume persistence `postgres_data`.
     - Environment configuration mapping `.env`.
   - `.env.example` & `.env.docker.example` updated with clean production settings.

3. **Queue Processing E2E Verification Test**:
   - `tests/Feature/QueueWorkerIntegrationTest.php`:
     - Dispatches `InsertEvent` job to database queue.
     - Runs `php artisan queue:work --once` programmatically.
     - Asserts `events` table contains the expected event row with all metadata and visitor hash intact.

---

## 2. Requirements & Verification Mapping

| Requirement | Description | Implementation Strategy |
|---|---|---|
| **QUEUE-01** | Database queue driver in v1 | `QUEUE_CONNECTION=database` in `.env.example` and `config/queue.php`. |
| **QUEUE-02** | Queue worker runs as persistent process | `deployment/supervisor/lumina-worker.conf` and Docker Supervisor config. |
| **DEPLOY-01** | Dockerfile for standalone app deployment | Multi-stage `Dockerfile` with PHP 8.3 + Nginx + Supervisor. |
| **DEPLOY-02** | `docker-compose.yml` for self-hosted VPS deploy | `docker-compose.yml` with `app` and `db` (Postgres 16) services. |

---

## 3. Deployment Artifact Specifications

### `deployment/supervisor/lumina-worker.conf`
```ini
[program:lumina-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
stopwaitsecs=360
```

### Multi-stage `Dockerfile`
- Uses Node.js to run `npm run build` and `npm run build:tracker`.
- Installs PHP extensions (`pdo_pgsql`, `pdo_mysql`, `bcmath`, `opcache`).
- Configures Nginx & Supervisor to serve web and execute worker processes.

### `docker-compose.yml`
- `app`: container built from `Dockerfile`, exposes port `8080:80`.
- `db`: `postgres:16-alpine` with healthcheck.
- Environment variables configured via `.env`.
