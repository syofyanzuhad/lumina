<?php

namespace Lumina\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Lumina\Core\Models\Site;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        return [
            'domain' => Str::lower($this->faker->unique()->domainName()),
            'owner_id' => $userModel::factory(),
        ];
    }
}
