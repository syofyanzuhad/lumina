<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Lumina\Core\Models\Goal;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_goals_for_site()
    {
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
    }

    public function test_user_can_create_goal()
    {
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
    }

    public function test_user_can_update_goal()
    {
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
    }

    public function test_user_can_delete_goal()
    {
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
    }

    public function test_user_cannot_manage_goals_of_other_sites()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $site = Site::factory()->create(['owner_id' => $user2->id]);

        $response = $this->actingAs($user1)->getJson("/sites/{$site->id}/goals");
        $response->assertStatus(403);
    }
}
