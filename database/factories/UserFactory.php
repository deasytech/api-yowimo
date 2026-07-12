<?php

namespace Database\Factories;

use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'clerk_user_id' => 'user_'.Str::random(24),
            'username' => fake()->unique()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'display_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'avatar_url' => null,
            'status' => UserStatus::Active,
        ];
    }

    public function deactivated(): static
    {
        return $this->state(fn () => [
            'status' => UserStatus::Deactivated,
        ]);
    }
}
