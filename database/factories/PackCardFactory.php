<?php

namespace Database\Factories;

use App\Enums\PackCardKind;
use App\Models\Pack;
use App\Models\PackCard;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackCard>
 */
class PackCardFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $kind = $this->faker->randomElement(PackCardKind::cases());

        return [
            'pack_id' => Pack::factory(),
            'kind' => $kind,
            'text' => $kind === PackCardKind::Truth
                ? $this->faker->sentence(10).'?'
                : $this->faker->sentence(8).'.',
            'position' => 0,
            'is_preview' => false,
        ];
    }

    public function preview(): static
    {
        return $this->state(fn () => ['is_preview' => true]);
    }
}
