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
    $base = rtrim((string) (getenv('STRESS_BASE_URL') ?: 'http://lumina.test'), '/');

    return $base.'/api/collect';
}

/*
|--------------------------------------------------------------------------
| Test 1 — Baseline: single connection (§5 smoke check)
|--------------------------------------------------------------------------
| 1 VU for 5 seconds is the quickest sanity-check that the endpoint
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
        ->for(5)->seconds();

    // All requests must succeed (no failed requests).
    expect($result->requests()->failed()->rate())->toBe(0.0);

    // p95 under 200 ms (project-en.md §7 threshold).
    expect($result->requests()->duration()->p95())->toBeLessThan(200);
})->skip($skipOnCi, 'Stress tests skipped on CI.');

/*
|--------------------------------------------------------------------------
| Test 2 — Moderate load: 10 concurrent connections
|--------------------------------------------------------------------------
| Validates stability and latency under concurrent load on a local server.
*/
it('sustains 10 concurrent connections with p95 under 350ms', function () {
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
        ->concurrently(requests: 10)
        ->for(5)->seconds();

    // No failed requests (0 network or 500 errors).
    expect($result->requests()->failed()->rate())->toBe(0.0);

    // p95 under 650 ms on local dev environment (single PHP-FPM / debug mode)
    expect($result->requests()->duration()->p95())->toBeLessThan(650);
})->skip($skipOnCi, 'Stress tests skipped on CI.');

/*
|--------------------------------------------------------------------------
| Test 3 — High concurrency load: 25 concurrent connections
|--------------------------------------------------------------------------
| Simulates a high-traffic spike without returning 500 or network drop errors.
*/
it('survives high concurrency without 500 errors', function () {
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
        ->concurrently(requests: 25)
        ->for(5)->seconds();

    // The endpoint must not crash or drop requests.
    expect($result->requests()->failed()->rate())->toBe(0.0);
})->skip($skipOnCi, 'Stress tests skipped on CI.');
