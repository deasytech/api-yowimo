<?php

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\Round;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Round>
 */
class RoundFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'number' => 1,
            'started_at' => now(),
            'completed_at' => null,
        ];
    }
}
