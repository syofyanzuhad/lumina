<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia;
use Lumina\Core\Models\Site;

test('site switcher data is shared with frontend', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    // /dashboard redirects server-side to include site_id when it is absent,
    // so the switcher test hits the canonical URL with site_id present.
    $response = $this->actingAs($user)->get('/dashboard?site_id='.$site->id);

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->has('sites', 1)
        ->has('active_site_id')
    );
});
