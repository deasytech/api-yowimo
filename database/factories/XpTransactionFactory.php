<?php

namespace Database\Factories;

use App\Enums\XpTransactionType;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<XpTransaction>
 */
class XpTransactionFactory extends Factory
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
            'type' => XpTransactionType::ChallengeCompleted,
            'amount' => 50,
            'game_session_id' => null,
            'idempotency_key' => null,
        ];
    }
}
