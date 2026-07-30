---
wave: 1
depends_on: []
files_modified:
  - config/queue.php
  - .env.example
  - .env.docker.example
  - deployment/supervisor/lumina-worker.conf
  - deployment/docker/entrypoint.sh
  - deployment/docker/nginx.conf
  - Dockerfile
  - docker-compose.yml
  - tests/Feature/QueueWorkerIntegrationTest.php
autonomous: true
---

# Phase 6 Plan: Queue Worker & Deployment Config

<threat_model>
- Unauthorized Database Access in Docker: Mitigated by environment variable secrets and non-root internal network configuration.
- Process Crashes & Data Loss: Mitigated by Supervisor auto-restart policies (`autorestart=true`, `stopwaitsecs=360`) for graceful queue shutdown.
- Stale Docker Builds: Mitigated by multi-stage Docker build copying freshly built Vite and tracker assets.
</threat_model>

<tasks>

<task id="01-queue-config-and-supervisor" autonomous="true">
  <action>Configure database queue default and Supervisor worker config</action>
  <description>Ensure `config/queue.php` uses `database` as default driver (`QUEUE_CONNECTION=database`). Update `.env.example`. Create `deployment/supervisor/lumina-worker.conf` configuring Supervisor to run 2 process instances of `php artisan queue:work database --sleep=3 --tries=3 --max-time=3600` with graceful shutdown timeouts.</description>
  <read_first>config/queue.php</read_first>
  <requirements>QUEUE-01, QUEUE-02</requirements>
  <acceptance_criteria>Supervisor config exists with auto-restart policies. `config/queue.php` uses database driver by default.</acceptance_criteria>
</task>

<task id="02-dockerfile-and-docker-compose" autonomous="true">
  <action>Create production Dockerfile and docker-compose.yml</action>
  <description>Create multi-stage `Dockerfile` building frontend assets (Node.js) and compiling PHP 8.3 environment with Supervisor and Nginx. Create `deployment/docker/nginx.conf` and `deployment/docker/entrypoint.sh` for auto-running migrations. Create `docker-compose.yml` defining `app` service and `db` service (Postgres 16 with persistent volume). Create `.env.docker.example` template.</description>
  <read_first>package.json</read_first>
  <requirements>DEPLOY-01, DEPLOY-02</requirements>
  <acceptance_criteria>`Dockerfile` builds successfully. `docker-compose.yml` orchestrates `app` and `db` services with environment variables.</acceptance_criteria>
</task>

<task id="03-queue-e2e-integration-test" autonomous="true">
  <action>Create end-to-end queue worker integration test</action>
  <description>Create `tests/Feature/QueueWorkerIntegrationTest.php`. Test dispatches `InsertEvent` job to database queue, executes queue worker inline (`Artisan::call('queue:work', ['--once' => true])`), and asserts that a matching record is inserted into the `events` table with visitor hash, path, and device type.</description>
  <read_first>packages/lumina-core/src/Jobs/InsertEvent.php</read_first>
  <requirements>QUEUE-01, QUEUE-02</requirements>
  <acceptance_criteria>Integration test passes completely (`php artisan test --filter=QueueWorkerIntegrationTest`).</acceptance_criteria>
</task>

</tasks>

## Artifacts this phase produces
- Supervisor config: `deployment/supervisor/lumina-worker.conf`
- Dockerfile: `Dockerfile`
- Docker Compose: `docker-compose.yml`
- Docker environment template: `.env.docker.example`
- Entrypoint script: `deployment/docker/entrypoint.sh`
- Nginx config: `deployment/docker/nginx.conf`
- Test: `tests/Feature/QueueWorkerIntegrationTest.php`

## Verification Criteria
- `php artisan test --filter=QueueWorkerIntegrationTest` passes.
- Supervisor config covers queue worker auto-restart and log redirect.
- `docker-compose.yml` validates correctly (`docker compose config` if docker available).
- `php artisan test --compact` passes completely.

## must_haves
- Default queue driver must be `database`.
- Docker setup must be self-contained for single-VPS deployment.
- Worker process shutdown must allow 360 seconds for long-running jobs to gracefully finish.
