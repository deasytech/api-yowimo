<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
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
            'provider' => 'paystack',
            'authorization_code' => 'AUTH_'.$this->faker->unique()->lexify('????????????'),
            'card_type' => $this->faker->randomElement(['visa', 'mastercard']),
            'last4' => $this->faker->numerify('####'),
            'exp_month' => $this->faker->numerify('##'),
            'exp_year' => (string) $this->faker->numberBetween(2027, 2032),
            'bank' => $this->faker->randomElement(['Access Bank', 'GTBank', 'Zenith Bank']),
            'is_default' => false,
        ];
    }
}
