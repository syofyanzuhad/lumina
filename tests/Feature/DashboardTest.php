<?php

use App\Models\User;
use Lumina\Core\Models\Site;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users with sites can visit the dashboard', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    $this->actingAs($user);

    // /dashboard redirects server-side to include site_id when it is absent.
    $response = $this->get(route('dashboard', ['site_id' => $site->id]));
    $response->assertOk();
});

test('authenticated users without sites are redirected to sites create page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('sites.create'));
});

test('dashboard accepts inclusion and exclusion filters and passes them to the view', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard', [
        'site_id' => $site->id,
        'country' => '!US',
        'device' => 'desktop',
    ]));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->where('filters.country', '!US')
        ->where('filters.device', 'desktop')
    );
});

test('breakdown endpoint responds with filtered data for inclusion and exclusion', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    $this->actingAs($user);

    $response = $this->getJson(route('dashboard.breakdown', [
        'site_id' => $site->id,
        'type' => 'devices',
        'country' => '!US',
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'type',
        'data',
    ]);
});
