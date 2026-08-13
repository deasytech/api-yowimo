<?php

namespace Database\Factories;

use App\Models\Pack;
use App\Models\PackPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PackPurchase>
 */
class PackPurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pack_id' => Pack::factory(),
            'user_id' => User::factory(),
            'wallet_transaction_id' => null,
        ];
    }
}
