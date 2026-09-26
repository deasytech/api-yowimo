<?php

namespace Database\Factories;

use App\Models\AnalyticsEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalyticsEvent>
 */
class AnalyticsEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array{user_id: Factory<User>, event: string, payload: array<empty, empty>, ip: string, device: string, country: string}
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event' => fake()->word().'_'.fake()->word(),
            'payload' => [],
            'ip' => fake()->ipv4(),
            'device' => fake()->userAgent(),
            'country' => fake()->countryCode(),
        ];
    }
}
