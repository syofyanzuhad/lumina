<?php

use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Models\Site;

test('public shared site renders custom events tab data when requested', function () {
    $site = Site::factory()->create([
        'is_public' => true,
        'share_token' => 'public-share-token-xyz',
    ]);

    $response = $this->get('/share/public-share-token-xyz?tab=events&event=click&property=button');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('activeTab', 'events')
            ->where('selectedEvent', 'click')
            ->where('selectedPropertyKey', 'button')
        );
});

test('public shared site breakdown endpoint returns json for valid public token', function () {
    $site = Site::factory()->create([
        'is_public' => true,
        'share_token' => 'public-share-token-xyz',
    ]);

    $response = $this->getJson('/share/public-share-token-xyz/breakdown?type=pages');

    $response->assertOk()
        ->assertJsonStructure(['type', 'data'])
        ->assertJson(['type' => 'pages']);
});

test('password protected shared site blocks breakdown endpoint without session auth', function () {
    $site = Site::factory()->create([
        'is_public' => true,
        'share_token' => 'pass-protected-token',
        'share_password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.zA0y65cVi', // 'password'
    ]);

    $response = $this->getJson('/share/pass-protected-token/breakdown?type=pages');
    $response->assertUnauthorized();

    // With authenticated session
    $responseWithAuth = $this->withSession(["share_auth_{$site->id}" => true])
        ->getJson('/share/pass-protected-token/breakdown?type=pages');

    $responseWithAuth->assertOk();
});
