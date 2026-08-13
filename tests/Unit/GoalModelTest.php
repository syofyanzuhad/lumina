<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Lumina\Core\Models\Goal;
use Lumina\Core\Models\Site;
use Tests\TestCase;

class GoalModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_goal_belongs_to_a_site(): void
    {
        $site = Site::factory()->create();
        $goal = Goal::create([
            'site_id' => $site->id,
            'name' => 'Signup Goal',
            'target_type' => 'path',
            'target_value' => '/thank-you',
        ]);

        $this->assertEquals($site->id, $goal->site->id);
        $this->assertEquals('Signup Goal', $goal->name);
        $this->assertEquals('path', $goal->target_type);
        $this->assertEquals('/thank-you', $goal->target_value);
    }
}
