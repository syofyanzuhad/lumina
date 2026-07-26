<?php

use App\Models\Site;
use App\Models\User;

test('it can create a site with normalized domain', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('sites.store'), [
        'domain' => 'https://www.example.com/path/to/page',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('sites', [
        'owner_id' => $user->id,
        'domain' => 'example.com',
    ]);
});

test('it requires a unique domain per user', function () {
    $user = User::factory()->create();
    Site::factory()->create(['owner_id' => $user->id, 'domain' => 'example.com']);

    $response = $this->actingAs($user)->post(route('sites.store'), [
        'domain' => 'example.com',
    ]);

    $response->assertInvalid(['domain']);
});
