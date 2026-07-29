<?php

use Lumina\Core\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('user can update active site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->put(route('active-site.update'), [
        'site_id' => $site->id,
    ]);

    $response->assertRedirect();
    $this->assertEquals($site->id, session('active_site_id'));
});

test('user cannot set active site to another users site', function () {
    $user = User::factory()->create();

    $otherUser = User::factory()->create();
    $otherSite = Site::factory()->create(['owner_id' => $otherUser->id]);

    $response = $this->actingAs($user)->put(route('active-site.update'), [
        'site_id' => $otherSite->id,
    ]);

    $response->assertInvalid(['site_id']);
});

test('inertia share exposes sites and active_site_id', function () {
    $user = User::factory()->create();
    $site1 = Site::factory()->create(['owner_id' => $user->id]);
    $site2 = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk();

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('sites', 2)
        ->where('active_site_id', $site1->id)
    );
});
