<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;

test('complete end to end analytics pipeline', function () {
    config(['queue.default' => 'database']);

    // 1. Create User and Site
    $user = User::factory()->create();
    $site = Site::factory()->create([
        'owner_id' => $user->id,
        'domain' => 'lumina-demo.com',
    ]);

    $this->assertEquals(0, DB::table('jobs')->count());
    $this->assertEquals(0, DB::table('events')->count());

    // 2. Submit Path B JS Tracking Script Ingest Event (POST /api/collect)
    $collectResponse = $this->postJson('/api/collect', [
        'domain' => 'lumina-demo.com',
        'path' => '/pricing',
        'referrer' => 'https://google.com',
        'screen_width' => 1440,
        'name' => 'plan_selected',
        'metadata' => ['plan' => 'pro'],
    ]);
    $collectResponse->assertStatus(204);

    // 3. Submit Path A Server-side Middleware Event
    $middlewareResponse = $this->withHeaders([
        'User-Agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0 like Mac OS X)',
        'Referer' => 'https://twitter.com',
    ])->get('http://lumina-demo.com/');
    $middlewareResponse->assertStatus(200);

    // Assert 2 jobs in queue
    $this->assertEquals(2, DB::table('jobs')->count());
    $this->assertEquals(0, DB::table('events')->count());

    // 4. Run Queue Worker to process both jobs
    Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);
    Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);

    // Assert jobs dequeued and 2 events inserted
    $this->assertEquals(0, DB::table('jobs')->count());
    $this->assertEquals(2, DB::table('events')->count());

    // 5. Query AnalyticsService directly to verify metric calculations
    $analytics = app(AnalyticsService::class);
    $overview = $analytics->getOverview($site, now()->subDays(1)->startOfDay(), now()->endOfDay());

    $this->assertEquals(2, $overview['total_pageviews']);
    $this->assertGreaterThanOrEqual(1, $overview['unique_visitors']);
    $this->assertCount(2, $overview['top_pages']);
    $this->assertCount(2, $overview['top_referrers']);
    $this->assertCount(1, $overview['custom_events']);

    // 6. Access Standalone Vue/Inertia Dashboard as Authenticated User
    $dashboardResponse = $this->actingAs($user)->get("/dashboard?site_id={$site->id}");
    $dashboardResponse->assertStatus(200);
    $dashboardResponse->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('activeSite.domain', 'lumina-demo.com')
        ->where('total_pageviews', 2)
    );
});
