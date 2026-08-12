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
