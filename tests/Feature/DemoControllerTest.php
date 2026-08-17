<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

test('demo route renders demo dashboard successfully with fallback static data when no site exists', function () {
    $response = $this->get('/demo');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('site.domain', 'demo.lumina.dev')
            ->where('requiresPassword', false)
        );
});

test('demo route renders real site data when a site exists', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create([
        'owner_id' => $user->id,
        'domain' => 'real-demo-site.com',
        'is_public' => false,
        'share_token' => null,
    ]);
    Event::factory()->count(3)->create(['site_id' => $site->id, 'created_at' => now()]);

    $response = $this->get('/demo');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('site.domain', 'real-demo-site.com')
            ->where('requiresPassword', false)
            ->has('total_pageviews')
        );
});

test('demo route delegates to the public share dashboard for the demo token', function () {
    $site = Site::factory()->create([
        'domain' => 'demo-delegate.com',
        'is_public' => true,
        'share_token' => 'demo-share-token-analytics',
    ]);

    $response = $this->get('/demo');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('site.domain', 'demo-delegate.com')
            ->where('requiresPassword', false)
        );
});

test('demo route honors the period query parameter', function () {
    $response = $this->get('/demo?period=7d');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('period', '7d')
        );
});
