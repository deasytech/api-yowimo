<?php

namespace Database\Factories;

use App\Enums\BadgeKey;
use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Badge>
 */
class BadgeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'key' => fake()->unique()->randomElement(BadgeKey::cases()),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'icon' => null,
        ];
    }
}
