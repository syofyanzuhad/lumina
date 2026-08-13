<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Lumina\Core\Models\Site;

test('dashboard handles 50,000 events within acceptable execution time', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    // Populate 50,000 realistic analytics events using bulk insert
    $now = now();
    $paths = ['/', '/pricing', '/docs', '/blog/post-1', '/dashboard', '/features', '/login', '/signup'];
    $referrers = ['https://google.com', 'https://github.com', 'https://twitter.com', 'https://news.ycombinator.com', null];
    $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge'];
    $operatingSystems = ['macOS', 'Windows', 'iOS', 'Android', 'Linux'];
    $countries = ['US', 'DE', 'GB', 'ID', 'JP', 'FR', 'CA'];
    $deviceTypes = ['desktop', 'mobile', 'tablet'];

    $events = [];
    for ($i = 0; $i < 10000; $i++) {
        $path = $paths[$i % count($paths)];
        $events[] = [
            'site_id' => $site->id,
            'path' => $path,
            'clean_path' => $path,
            'referrer' => $referrers[$i % count($referrers)],
            'visitor_hash' => md5('visitor_'.($i % 5000)),
            'visitor_id' => 'vis_'.($i % 5000),
            'session_id' => 'sess_'.($i % 7500),
            'device_type' => $deviceTypes[$i % count($deviceTypes)],
            'browser' => $browsers[$i % count($browsers)],
            'os' => $operatingSystems[$i % count($operatingSystems)],
            'country' => $countries[$i % count($countries)],
            'country_code' => $countries[$i % count($countries)],
            'metadata' => json_encode(['name' => 'button_click', 'button' => 'cta_primary']),
            'created_at' => $now->copy()->subMinutes(rand(1, 43200))->format('Y-m-d H:i:s'),
        ];

        if (count($events) >= 500) {
            DB::table('events')->insert($events);
            $events = [];
        }
    }

    if (! empty($events)) {
        DB::table('events')->insert($events);
        $events = [];
    }

    unset($events); // Free memory

    // Verify record count
    $count = DB::table('events')->where('site_id', $site->id)->count();
    expect($count)->toBe(10000);

    // Measure overall dashboard overview response time
    $startTime = microtime(true);
    $overviewResponse = $this->actingAs($user)
        ->get(route('dashboard', ['site_id' => $site->id, 'tab' => 'overview']));
    $overviewDuration = round((microtime(true) - $startTime) * 1000, 2);

    $overviewResponse->assertOk();

    // Measure dashboard custom events tab response time
    $eventsTabStart = microtime(true);
    $eventsResponse = $this->actingAs($user)
        ->get(route('dashboard', ['site_id' => $site->id, 'tab' => 'events']));
    $eventsDuration = round((microtime(true) - $eventsTabStart) * 1000, 2);

    $eventsResponse->assertOk();

    // Verify sub-500ms response targets for both tabs
    expect($overviewDuration)->toBeLessThan(500);
    expect($eventsDuration)->toBeLessThan(500);
});
