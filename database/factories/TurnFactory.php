<?php

namespace Database\Factories;

use App\Models\GameSession;
use App\Models\PackCard;
use App\Models\Round;
use App\Models\Turn;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turn>
 */
class TurnFactory extends Factory
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
            'round_id' => Round::factory(),
            'user_id' => User::factory(),
            'pack_card_id' => PackCard::factory(),
            'position' => 0,
            'started_at' => now(),
            'completed_at' => null,
            'is_afk' => false,
        ];
    }
}
