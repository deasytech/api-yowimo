<?php

namespace Database\Factories;

use App\Enums\WalletTransactionType;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WalletTransaction>
 */
class WalletTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $amount = $this->faker->randomElement([50, 100, 250, 500]);

        return [
            'wallet_id' => Wallet::factory(),
            'type' => WalletTransactionType::TopUp,
            'amount' => $amount,
            'balance_after' => $amount,
            'idempotency_key' => null,
            'description' => null,
            'metadata' => null,
        ];
    }

    public function debit(int $amount): static
    {
        return $this->state(fn () => [
            'type' => WalletTransactionType::Purchase,
            'amount' => -abs($amount),
        ]);
    }
}
