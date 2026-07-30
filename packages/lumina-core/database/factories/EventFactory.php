<?php

namespace Lumina\Core\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Lumina\Core\Enums\DeviceType;
use Lumina\Core\Models\Event;
use Lumina\Core\Models\Site;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

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
            'country' => $countryCode = $this->faker->optional(0.9)->countryCode(),
            'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'browser_version' => $this->faker->numberBetween(80, 120).'.0',
            'os' => $this->faker->randomElement(['Windows', 'macOS', 'iOS', 'Android']),
            'os_version' => $this->faker->numberBetween(10, 17).'.0',
            'country_code' => $countryCode,
            'country_name' => $countryCode ? $this->faker->country() : null,
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
