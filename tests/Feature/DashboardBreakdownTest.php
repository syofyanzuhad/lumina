<?php

use App\Models\User;
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
