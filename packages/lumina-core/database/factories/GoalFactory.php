<?php

namespace Lumina\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Lumina\Core\Models\Goal;

/**
 * @extends Factory<Goal>
 */
class GoalFactory extends Factory
{
    protected $model = Goal::class;

    public function definition(): array
    {
        return [
            'site_id' => SiteFactory::new(),
            'name' => fake()->word(),
            'target_type' => 'path',
            'target_value' => '/'.fake()->slug(),
        ];
    }
}
