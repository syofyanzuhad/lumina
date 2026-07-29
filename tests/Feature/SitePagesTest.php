<?php

use Lumina\Core\Models\Site;
use App\Models\User;
use Inertia\Testing\AssertableInertia;

test('index page renders sites list', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('sites.index'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Sites/Index')
        ->has('sites', 1)
    );
});

test('create page renders form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('sites.create'));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Sites/Create')
    );
});

test('show page renders site details', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->get(route('sites.show', $site));

    $response->assertOk();
    $response->assertInertia(fn (AssertableInertia $page) => $page
        ->component('Sites/Show')
        ->has('site')
    );
});
