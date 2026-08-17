<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Goal;
use Lumina\Core\Models\Site;
use Lumina\Core\Services\AnalyticsService;

test('milestone v1 1 full persona lifecycle and features', function () {
    config(['queue.default' => 'database']);

    // Step 1: Site setup & user creation
    $user = User::factory()->create();
    $site = Site::factory()->create([
        'owner_id' => $user->id,
        'domain' => 'v11-demo.com',
        'is_public' => true,
        'share_token' => 'v11-share-token-12345678901234',
        'share_password' => Hash::make('secret123'),
    ]);

    // Step 2: Ingest event with UA + GeoIP via POST /api/collect (Custom event & Path tracking)
    $uaHeader = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';

    // X-Country is the first-party override that is honored regardless of
    // proxy trust (CF-IPCountry would be ignored without TRUSTED_PROXIES).
    $collectResponse = $this->withHeaders([
        'User-Agent' => $uaHeader,
        'X-Country' => 'US',
    ])->postJson('/api/collect', [
        'domain' => 'v11-demo.com',
        'path' => '/signup',
        'referrer' => 'https://google.com',
        'screen_width' => 1440,
        'name' => 'signup_completed',
        'metadata' => ['plan' => 'pro'],
    ]);

    $collectResponse->assertStatus(204);
    $this->assertEquals(1, DB::table('jobs')->count());

    // Step 3: Process Queue Worker to insert event
    Artisan::call('queue:work', ['connection' => 'database', '--once' => true]);
    $this->assertEquals(0, DB::table('jobs')->count());
    $this->assertEquals(1, DB::table('events')->count());

    $event = Event::first();
    $this->assertEquals('Chrome', $event->browser);
    $this->assertEquals('OS X', $event->os);
    $this->assertEquals('US', $event->country_code);
    $this->assertEquals('United States', $event->country_name);

    // Step 4: Create Goal & verify conversion metrics calculation
    $goal = Goal::create([
        'site_id' => $site->id,
        'name' => 'Pro Signups',
        'target_type' => 'custom_event',
        'target_value' => 'signup_completed',
    ]);

    $analytics = app(AnalyticsService::class);
    $start = now()->subDays(1)->startOfDay();
    $end = now()->endOfDay();

    $goalsMetrics = $analytics->getGoals($site, $start, $end);
    $this->assertCount(1, $goalsMetrics);
    $this->assertEquals('Pro Signups', $goalsMetrics->first()['name']);
    $this->assertEquals(1, $goalsMetrics->first()['completions']);

    // Step 5: Test Data Streaming Export (CSV & JSON)
    $csvResponse = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'events', 'format' => 'csv']));
    $csvResponse->assertOk();
    $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');

    ob_start();
    $csvResponse->sendContent();
    $csvContent = ob_get_clean();
    $this->assertStringContainsString('signup_completed', $csvContent);

    $jsonResponse = $this->actingAs($user)
        ->get(route('sites.export', ['site' => $site, 'type' => 'events', 'format' => 'json']));
    $jsonResponse->assertOk();

    ob_start();
    $jsonResponse->sendContent();
    $jsonContent = ob_get_clean();
    $this->assertStringContainsString('signup_completed', $jsonContent);

    // Step 6: Public Share Dashboard & Password Authentication
    $publicGet = $this->get('/share/v11-share-token-12345678901234');
    $publicGet->assertStatus(200);
    $publicGet->assertInertia(fn (Assert $page) => $page
        ->component('Share/Show')
        ->where('requiresPassword', true)
    );

    $authPost = $this->post('/share/v11-share-token-12345678901234/password', [
        'password' => 'secret123',
    ]);
    $authPost->assertRedirect('/share/v11-share-token-12345678901234');

    $authenticatedShareGet = $this->get('/share/v11-share-token-12345678901234');
    $authenticatedShareGet->assertStatus(200);
    $authenticatedShareGet->assertInertia(fn (Assert $page) => $page
        ->component('Share/Show')
        ->where('requiresPassword', false)
        ->where('site.domain', 'v11-demo.com')
    );
});
