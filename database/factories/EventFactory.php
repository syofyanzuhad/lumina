<?php

namespace Database\Factories;

use App\Enums\DeviceType;
use App\Models\Event;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'path' => '/'.$this->faker->slug(),
            'referrer' => $this->faker->optional(0.7)->url(),
            'visitor_hash' => hash('sha256', $this->faker->ipv4().$this->faker->userAgent().Str::random(16)),
            'device_type' => $this->faker->randomElement(DeviceType::cases()),
            'country' => $this->faker->optional(0.9)->countryCode(),
            'created_at' => $this->faker->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function desktop(): self
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => DeviceType::Desktop,
        ]);
    }

    public function mobile(): self
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => DeviceType::Mobile,
        ]);
    }

    public function tablet(): self
    {
        return $this->state(fn (array $attributes) => [
            'device_type' => DeviceType::Tablet,
        ]);
    }
}
