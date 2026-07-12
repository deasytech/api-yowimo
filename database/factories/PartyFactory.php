<?php

namespace Database\Factories;

use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\GameType;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Party>
 */
class PartyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mode = $this->faker->randomElement(PartyMode::cases());

        return [
            'host_id' => User::factory(),
            'game_type_id' => GameType::factory(),
            'pack_id' => null,
            'room_code' => Str::upper(Str::random(6)),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->optional()->paragraph(),
            'mode' => $mode,
            'visibility' => $this->faker->randomElement(PartyVisibility::cases()),
            'status' => $this->faker->randomElement(PartyStatus::publiclyVisible()),
            'max_players' => $this->faker->randomElement([6, 8, 12, 16, 50]),
            'players_count' => $this->faker->numberBetween(1, 10),
            'likes_count' => 0,
            'starts_at' => $this->faker->optional(0.6)->dateTimeBetween('now', '+1 week'),
            'location' => $mode === PartyMode::Online ? null : [
                'venue_name' => $this->faker->company(),
                'address' => $this->faker->address(),
                'latitude' => $this->faker->latitude(),
                'longitude' => $this->faker->longitude(),
            ],
            'tags' => $this->faker->randomElements(['18+', 'Spicy', 'Voice', 'Uni', 'Fast', 'Teams', 'Chill'], $this->faker->numberBetween(0, 3)),
            'cover_image_url' => null,
            'gradient' => [$this->faker->hexColor(), $this->faker->hexColor()],
            'is_sponsored' => false,
            'sponsor_name' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => PartyStatus::Draft]);
    }

    public function live(): static
    {
        return $this->state(fn () => ['status' => PartyStatus::Live]);
    }

    public function public(): static
    {
        return $this->state(fn () => ['visibility' => PartyVisibility::Public]);
    }

    public function private(): static
    {
        return $this->state(fn () => ['visibility' => PartyVisibility::Private]);
    }

    public function sponsored(): static
    {
        return $this->state(fn () => [
            'is_sponsored' => true,
            'sponsor_name' => $this->faker->company(),
        ]);
    }
}
