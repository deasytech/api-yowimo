<?php

namespace Database\Factories;

use App\Enums\PackCategory;
use App\Models\GameType;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Pack>
 */
class PackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true).' Pack';
        $truths = $this->faker->numberBetween(15, 40);
        $dares = $this->faker->numberBetween(15, 40);

        return [
            'game_type_id' => GameType::factory(),
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(1000, 9999),
            'name' => Str::title($name),
            'emoji' => $this->faker->randomElement(['🌶️', '💼', '👨‍👩‍👧', '💕', '🎉', '⚡', '💫']),
            'tag' => $this->faker->optional(0.4)->randomElement(['Limited', 'Hot', 'New', 'Corporate']),
            'category' => $this->faker->randomElement(PackCategory::cases()),
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomElement([0, 40, 60, 80, 120, 150, 300]),
            'truths_count' => $truths,
            'dares_count' => $dares,
            'cards_count' => $truths + $dares,
            'cover_image_url' => null,
            'gradient' => [$this->faker->hexColor(), $this->faker->hexColor()],
            'is_featured' => false,
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }

    public function featured(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }
}
