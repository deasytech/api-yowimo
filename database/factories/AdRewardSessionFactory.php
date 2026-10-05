<?php

namespace Database\Factories;

use App\Enums\AdRewardSessionStatus;
use App\Models\AdRewardSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<AdRewardSession>
 */
class AdRewardSessionFactory extends Factory
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
            'token_hash' => AdRewardSession::hashToken(Str::random(64)),
            'status' => AdRewardSessionStatus::Pending,
            'reward_amount' => 1,
            'ad_network_transaction_id' => null,
            'wallet_transaction_id' => null,
            'metadata' => null,
            'expires_at' => now()->addMinutes(15),
            'credited_at' => null,
        ];
    }

    public function credited(): static
    {
        return $this->state(fn () => [
            'status' => AdRewardSessionStatus::Credited,
            'credited_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => AdRewardSessionStatus::Expired,
            'expires_at' => now()->subMinute(),
        ]);
    }
}
