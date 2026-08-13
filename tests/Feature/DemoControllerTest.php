<?php

use Inertia\Testing\AssertableInertia as Assert;

test('demo route renders demo dashboard successfully with fallback static data when no site exists', function () {
    $response = $this->get('/demo');

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Share/Show')
            ->where('site.domain', 'demo.lumina.dev')
            ->where('requiresPassword', false)
        );
});
