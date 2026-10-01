<?php

namespace Database\Factories;

use App\Enums\GameIntensity;
use App\Models\GameType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GameType>
 */
class GameTypeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Truth or Dare', 'Never Have I Ever', 'Most Likely To', 'Would You Rather',
            'Two Truths and a Lie', 'Rapid Fire', 'Charades', 'Hot Seat',
            'Guess the Song', 'Guess the Movie', 'Couple Quiz', 'Family Feud Night',
            'Corporate Icebreaker', 'Party Mixer', 'Wildcard Mayhem',
        ]);

        return [
            'slug' => Str::slug($name).'-'.Str::random(6),
            'name' => $name,
            'emoji' => $this->faker->randomElement(['🎭', '🙊', '🔥', '🤔', '🎯', '🃏', '💃', '🎤']),
            'tagline' => $this->faker->sentence(4),
            'audience' => $this->faker->randomElement(['Friends', 'All', 'Teams', 'Family', 'Couples', 'Adults']),
            'intensity' => $this->faker->randomElement(GameIntensity::cases()),
            // Free by default: cost now actually gates party creation
            // (PartyService debits it), so a random non-zero value here
            // would make any test that creates a party for a factory-default
            // game type flaky depending on the host's wallet balance. Tests
            // that specifically want a paid game type set 'cost' themselves.
            'cost' => 0,
            'image_url' => null,
            'gradient' => [$this->faker->hexColor(), $this->faker->hexColor()],
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(0, 100),
        ];
    }
}
