<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Lumina\Core\Models\Site;

test('user can update active site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->put(route('active-site.update'), [
        'site_id' => $site->id,
    ]);

    $response->assertRedirect();
    $this->assertEquals($site->id, session('active_site_id'));
    expect($user->fresh()->last_active_site_id)->toBe($site->id);
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

    // /dashboard redirects server-side to include site_id when it is absent.
    $response = $this->actingAs($user)->get('/dashboard?site_id='.$site1->id);
    $response->assertOk();

    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('sites', 2)
        ->where('active_site_id', $site1->id)
    );
});
