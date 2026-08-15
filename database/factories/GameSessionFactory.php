<?php

namespace Database\Factories;

use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use App\Models\Pack;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'party_id' => Party::factory(),
            'host_id' => User::factory(),
            'pack_id' => Pack::factory(),
            'status' => GameSessionStatus::Running,
            'rounds_count' => 10,
            'current_round_number' => 1,
            'turn_order' => [],
            'current_turn_index' => 0,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => GameSessionStatus::Completed,
            'ended_at' => now(),
        ]);
    }
}
