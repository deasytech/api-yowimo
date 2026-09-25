<?php

namespace Database\Factories;

use App\Enums\PartyMemberStatus;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PartyMember>
 */
class PartyMemberFactory extends Factory
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
            'status' => PartyMemberStatus::Active,
            'joined_at' => now(),
            'left_at' => null,
        ];
    }

    /**
     * @return Factory<PartyMember>
     */
    public function left(): Factory
    {
        return $this->state([
            'status' => PartyMemberStatus::Left,
            'left_at' => now(),
        ]);
    }
}
