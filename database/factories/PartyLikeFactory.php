<?php

namespace Database\Factories;

use App\Models\Party;
use App\Models\PartyLike;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartyLike>
 */
class PartyLikeFactory extends Factory
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
            'user_id' => User::factory(),
        ];
    }
}
