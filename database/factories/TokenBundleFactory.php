<?php

namespace Database\Factories;

use App\Models\TokenBundle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TokenBundle>
 */
class TokenBundleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->randomElement(['Starter', 'Party', 'Legend', 'Whale', 'Booster', 'Champion']);
        $tokens = $this->faker->randomElement([100, 500, 1500, 5000]);

        return [
            'slug' => Str::slug($name),
            'name' => $name,
            'tokens' => $tokens,
            'price' => round($tokens * 0.018, 2),
            'currency' => 'USD',
            'badge' => $this->faker->optional(0.3)->randomElement(['Popular', 'Best value']),
            'gradient' => [$this->faker->hexColor(), $this->faker->hexColor()],
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
