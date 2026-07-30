<?php

namespace Lumina\Core\Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
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
        $userModel = config('auth.providers.users.model', User::class);

        return [
            'domain' => Str::lower($this->faker->unique()->domainName()),
            'owner_id' => $userModel::factory(),
        ];
    }

    /**
     * Indicate that the site is public.
     */
    public function public(?string $token = null): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
            'share_token' => $token ?? Str::random(32),
        ]);
    }

    /**
     * Indicate that the site is public and password-protected.
     */
    public function passwordProtected(string $password = 'secret'): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
            'share_token' => Str::random(32),
            'share_password' => Hash::make($password),
        ]);
    }
}
