<?php

namespace Database\Factories;

use App\Enums\VoteCategory;
use App\Models\Turn;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vote>
 */
class VoteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'turn_id' => Turn::factory(),
            'voter_id' => User::factory(),
            'category' => VoteCategory::Winner,
        ];
    }
}
