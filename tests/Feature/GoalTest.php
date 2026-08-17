<?php

use App\Models\User;
use Lumina\Core\Models\Goal;
use Lumina\Core\Models\Site;

test('user can get goals for site', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    $goal = Goal::create([
        'site_id' => $site->id,
        'name' => 'Signup',
        'target_type' => 'custom_event',
        'target_value' => 'signup',
    ]);

    $response = $this->actingAs($user)->getJson("/sites/{$site->id}/goals");

    $response->assertStatus(200);
    $response->assertJsonFragment(['name' => 'Signup']);
});

test('user can create goal', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);

    $response = $this->actingAs($user)->postJson("/sites/{$site->id}/goals", [
        'name' => 'Checkout',
        'target_type' => 'path',
        'target_value' => '/checkout',
    ]);

    $response->assertStatus(201);
    $this->assertDatabaseHas('goals', [
        'site_id' => $site->id,
        'name' => 'Checkout',
        'target_type' => 'path',
        'target_value' => '/checkout',
    ]);
});

test('user can update goal', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    $goal = Goal::create([
        'site_id' => $site->id,
        'name' => 'Signup',
        'target_type' => 'custom_event',
        'target_value' => 'signup',
    ]);

    $response = $this->actingAs($user)->putJson("/sites/{$site->id}/goals/{$goal->id}", [
        'name' => 'New Signup',
        'target_type' => 'path',
        'target_value' => '/welcome',
    ]);

    $response->assertStatus(200);
    $this->assertDatabaseHas('goals', [
        'id' => $goal->id,
        'name' => 'New Signup',
        'target_type' => 'path',
        'target_value' => '/welcome',
    ]);
});

test('user can delete goal', function () {
    $user = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user->id]);
    $goal = Goal::create([
        'site_id' => $site->id,
        'name' => 'Signup',
        'target_type' => 'custom_event',
        'target_value' => 'signup',
    ]);

    $response = $this->actingAs($user)->deleteJson("/sites/{$site->id}/goals/{$goal->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('goals', [
        'id' => $goal->id,
    ]);
});

test('user cannot manage goals of other sites', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $site = Site::factory()->create(['owner_id' => $user2->id]);

    $response = $this->actingAs($user1)->getJson("/sites/{$site->id}/goals");
    $response->assertStatus(403);
});
