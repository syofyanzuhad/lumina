<?php

/**
 * Stress tests for the Lumina Analytics ingest endpoint.
 *
 * These tests verify the §5 load-test requirement from project-en.md:
 * "hit /api/collect with simulated load (50 req/s for 1 minute) —
 * record p95 response time; p95 must stay under 200ms (§7 threshold)."
 *
 * IMPORTANT: These tests hit a *running* HTTP server. They will not work with
 * the in-process Laravel test client. Run a real server first:
 *
 *   php artisan serve          (local dev)
 *   herd link lumina           (Laravel Herd)
 *   # or point STRESS_BASE_URL at staging/production
 *
 * Then run:
 *   php artisan test --compact --filter=StressTest
 *
 * To skip on CI (tests are slow by nature — 60s each):
 *   The tests auto-skip when GITHUB_ACTIONS=true or CI=true.
 * To run on CI explicitly:
 *   FORCE_STRESS=true php artisan test --compact --filter=StressTest
 *
 * The STRESS_BASE_URL environment variable controls the target server.
 * It must have a site registered with domain "stress-test.example.com".
 * The test seeds one automatically in the local DB before running, but for
 * staging/production you must create it manually.
 */

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Lumina\Core\Models\Site;

use function Pest\Stressless\stress;

/*
|--------------------------------------------------------------------------
| Global skip condition
|--------------------------------------------------------------------------
| Skip automatically on CI pipelines unless explicitly opted in.
| Stress tests are slow (60 s each) — they should run locally or in a
| dedicated performance pipeline, not on every PR.
*/
$skipOnCi = (getenv('CI') === 'true' || getenv('GITHUB_ACTIONS') === 'true')
    && getenv('FORCE_STRESS') !== 'true';

/*
|--------------------------------------------------------------------------
| Shared setup: ensure the test domain exists in the local DB
|--------------------------------------------------------------------------
| Stressless hits a real HTTP server, but we can seed the DB before the
| test runs so the collect endpoint finds a registered site.
| This is a no-op when STRESS_BASE_URL points at staging/production
| (where the site must already exist).
*/
beforeEach(function () {
    $user = User::firstOrCreate(
        ['email' => 'stress-test@lumina.test'],
        ['name' => 'Stress Tester', 'password' => bcrypt('password')],
    );

    Site::firstOrCreate(
        ['domain' => 'stress-test.example.com'],
        ['owner_id' => $user->id],
    );

    // Bust the domain cache so CollectController sees the freshly created site.
    Cache::forget('lumina_site_lookup:stress-test.example.com');
})->skip($skipOnCi, 'Stress tests skipped on CI. Set FORCE_STRESS=true to run.');

/*
|--------------------------------------------------------------------------
| Helpers
|--------------------------------------------------------------------------
*/
function collectUrl(): string
{
    $base = rtrim((string) (getenv('STRESS_BASE_URL') ?: 'http://localhost:8000'), '/');

    return $base.'/api/collect';
}

/*
|--------------------------------------------------------------------------
| Test 1 — Baseline: single connection (§5 smoke check)
|--------------------------------------------------------------------------
| 1 VU for 10 seconds is the quickest sanity-check that the endpoint
| responds and that p95 < 200 ms at minimal load. Run this first.
*/
it('handles single concurrent connection with p95 under 200ms', function () {
    $result = stress(collectUrl())
        ->post([
            'domain' => 'stress-test.example.com',
            'path' => '/blog/stress-test',
            'referrer' => 'https://google.com',
            'screen_width' => 1440,
        ])
        ->headers([
            'Content-Type' => 'application/json',
            'Origin' => 'https://stress-test.example.com',
        ])
        ->concurrently(requests: 1)
        ->for(10)->seconds();

    // All requests must succeed (204) or be rate-limited (204 silently) —
    // no 500s allowed.
    expect($result->requests()->failed()->count())->toBe(0);

    // p95 under 200 ms (project-en.md §7 threshold).
    expect($result->requests()->duration()->p95())->toBeLessThan(200);
})->skip($skipOnCi, 'Stress tests skipped on CI.');

/*
|--------------------------------------------------------------------------
| Test 2 — Target load: 50 concurrent connections for 60 s (§5 requirement)
|--------------------------------------------------------------------------
| This is the exact test specified in §5 of project-en.md:
| "50 req/s for 1 minute — p95 response time must stay under 200ms."
|
| Note: Stressless uses VUs (virtual users), not req/s. 50 concurrent VUs
| each firing as fast as the server responds approximates ≥50 req/s once
| the server is warmed up.
*/
it('sustains 50 concurrent connections for 60 seconds with p95 under 200ms', function () {
    $result = stress(collectUrl())
        ->post([
            'domain' => 'stress-test.example.com',
            'path' => '/blog/stress-test',
            'referrer' => 'https://google.com',
            'screen_width' => 1440,
        ])
        ->headers([
            'Content-Type' => 'application/json',
            'Origin' => 'https://stress-test.example.com',
        ])
        ->concurrently(requests: 50)
        ->for(60)->seconds();

    // No 500s. 429s from rate limiting are fine (they return 204 silently).
    expect($result->requests()->failed()->count())->toBe(0);

    // p95 under 200 ms — the documented threshold from §7.
    expect($result->requests()->duration()->p95())->toBeLessThan(200);

    // Median must be fast too.
    expect($result->requests()->duration()->med())->toBeLessThan(100);
})->skip($skipOnCi, 'Stress tests skipped on CI.');

/*
|--------------------------------------------------------------------------
| Test 3 — Spike: 100 concurrent connections for 30 s
|--------------------------------------------------------------------------
| Simulates a traffic spike above the expected sustained load.
| p95 threshold is relaxed to 500 ms — spikes are expected to be slower,
| but the endpoint must not crash (no 500s).
*/
it('survives a 100-connection spike with no 500 errors', function () {
    $result = stress(collectUrl())
        ->post([
            'domain' => 'stress-test.example.com',
            'path' => '/blog/stress-test',
            'referrer' => 'https://twitter.com',
            'screen_width' => 375,
        ])
        ->headers([
            'Content-Type' => 'application/json',
            'Origin' => 'https://stress-test.example.com',
        ])
        ->concurrently(requests: 100)
        ->for(30)->seconds();

    // The spike may hit rate limits but must not return 500s.
    expect($result->requests()->failed()->count())->toBe(0);

    // p95 under 500 ms under spike — relaxed but still bounded.
    expect($result->requests()->duration()->p95())->toBeLessThan(500);
})->skip($skipOnCi, 'Stress tests skipped on CI.');
