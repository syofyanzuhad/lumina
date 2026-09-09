<?php

use App\Models\User;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

test('breakdown endpoint requires authentication', function () {
    $response = $this->getJson(route('dashboard.breakdown'));
    $response->assertUnauthorized();
});

test('breakdown endpoint returns JSON breakdown for owned site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->getJson(route('dashboard.breakdown', [
        'site_id' => $site->id,
        'type' => 'pages',
        'period' => '30d',
    ]));

    $response->assertOk()
        ->assertJsonStructure(['type', 'data'])
        ->assertJson(['type' => 'pages']);
});

test('user cannot query breakdown for site owned by another user', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $owner->id]);

    $response = $this->actingAs($otherUser)->getJson(route('dashboard.breakdown', [
        'site_id' => $site->id,
        'type' => 'pages',
    ]));

    $response->assertNotFound();
});

test('breakdown endpoint returns graceful empty response for invalid type', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->getJson(route('dashboard.breakdown', [
        'site_id' => $site->id,
        'type' => 'invalid_type',
    ]));

    $response->assertOk()
        ->assertJson([
            'type' => 'invalid_type',
            'data' => [],
        ]);
});

test('breakdown endpoint handles missing type parameter gracefully', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->getJson(route('dashboard.breakdown', [
        'site_id' => $site->id,
    ]));

    $response->assertOk()
        ->assertJson([
            'type' => null,
            'data' => [],
        ]);
});

test('breakdown endpoint respects period parameter for today', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    Event::factory()->create([
        'site_id' => $site->id,
        'path' => '/today-page',
        'clean_path' => '/today-page',
        'created_at' => now(),
    ]);

    Event::factory()->create([
        'site_id' => $site->id,
        'path' => '/old-page',
        'clean_path' => '/old-page',
        'created_at' => now()->subDays(5),
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.breakdown', [
        'site_id' => $site->id,
        'type' => 'pages',
        'period' => 'today',
    ]));

    $response->assertOk();
    $paths = collect($response->json('data'))->pluck('path')->all();
    expect($paths)->toContain('/today-page')
        ->and($paths)->not->toContain('/old-page');
});

test('breakdown endpoint respects custom date range', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    Event::factory()->create([
        'site_id' => $site->id,
        'path' => '/in-range',
        'clean_path' => '/in-range',
        'created_at' => '2026-08-15 12:00:00',
    ]);

    Event::factory()->create([
        'site_id' => $site->id,
        'path' => '/out-range',
        'clean_path' => '/out-range',
        'created_at' => '2026-09-05 12:00:00',
    ]);

    $response = $this->actingAs($user)->getJson(route('dashboard.breakdown', [
        'site_id' => $site->id,
        'type' => 'pages',
        'period' => 'custom',
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-31',
    ]));

    $response->assertOk();
    $paths = collect($response->json('data'))->pluck('path')->all();
    expect($paths)->toContain('/in-range')
        ->and($paths)->not->toContain('/out-range');
});

test('breakdown endpoint clamps limit between 1 and 100', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    for ($i = 1; $i <= 5; $i++) {
        Event::factory()->create([
            'site_id' => $site->id,
            'path' => "/page-{$i}",
            'clean_path' => "/page-{$i}",
            'created_at' => now()->subHour(),
        ]);
    }

    // Limit less than 1 clamped to 1
    $resMin = $this->actingAs($user)->getJson(route('dashboard.breakdown', [
        'site_id' => $site->id,
        'type' => 'pages',
        'limit' => 0,
    ]))->assertOk();
    expect($resMin->json('data'))->toHaveCount(1);

    // Specific valid limit
    $resTwo = $this->actingAs($user)->getJson(route('dashboard.breakdown', [
        'site_id' => $site->id,
        'type' => 'pages',
        'limit' => 2,
    ]))->assertOk();
    expect($resTwo->json('data'))->toHaveCount(2);
});
