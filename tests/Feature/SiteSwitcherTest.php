<?php

use App\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('site switcher data is shared with frontend', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('sites', 1)
        ->has('active_site_id')
    );
});
