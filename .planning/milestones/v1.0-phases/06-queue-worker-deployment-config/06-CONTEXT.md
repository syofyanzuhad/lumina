# Phase 6: Queue Worker & Deployment Config - Context

**Gathered:** 2026-07-30
**Status:** Active

<domain>
## Phase Boundary

Phase 6 sets up persistent queue processing and Docker deployment configuration for Lumina:
1. **Queue Configuration & Supervisor**:
   - Verify `config/queue.php` database connection default (`QUEUE_CONNECTION=database`).
   - Supervisor worker configuration file (`deployment/supervisor/lumina-worker.conf`) for VPS deployment running `php artisan queue:work --tries=3 --timeout=90`.
2. **Dockerization (VPS / Self-Hosted Deploy)**:
   - `Dockerfile` for PHP 8.3-FPM application with required extensions (pdo_pgsql, pdo_mysql, bcmath, zip, etc.), Supervisor, Nginx, and Node (or built frontend assets).
   - `docker-compose.yml` service orchestration:
     - `app`: Lumina web server + queue worker managed by Supervisor.
     - `db`: PostgreSQL 16 database service with persistent volume (`postgres_data`).
     - `nginx`: Reverse proxy / static file server (or embedded Nginx in container).
   - `.env.docker.example` environment template configured for Docker Compose setup.
3. **Queue Processing Verification**:
   - Integration test verifying end-to-end event execution: dispatch `InsertEvent` job -> process queue -> assert `Event` record created in database.

</domain>

<decisions>
- **D-01 (Queue Driver):** Use `database` driver as standard default for v1. Zero extra infra (no Redis/Valkey required for MVP).
- **D-02 (Deployment Target):** First-class Docker Compose for VPS self-hosting + Laravel Cloud environment compatibility.
- **D-03 (Process Supervisor):** Use Supervisor inside the container / VPS to manage `php artisan queue:work` persistently.
</decisions>

<canonical_refs>
- `project-en.md` §3.4 & §4 — Queue processing architecture, VPS Docker deployment.
- `.planning/REQUIREMENTS.md` — QUEUE-01, QUEUE-02, DEPLOY-01, DEPLOY-02.
</canonical_refs>
