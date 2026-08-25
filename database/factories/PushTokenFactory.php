<?php

namespace Database\Factories;

use App\Enums\PushPlatform;
use App\Models\PushToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PushToken>
 */
class PushTokenFactory extends Factory
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
            'token' => $this->faker->uuid(),
            'platform' => $this->faker->randomElement(PushPlatform::cases()),
        ];
    }
}
