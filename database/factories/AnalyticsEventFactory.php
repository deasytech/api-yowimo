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
     * @return array<string, mixed>
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
