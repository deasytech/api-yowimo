<?php

namespace Database\Factories;

use App\Enums\SponsorshipInviteStatus;
use App\Enums\SponsorshipScope;
use App\Models\Party;
use App\Models\SponsorshipInvite;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SponsorshipInvite>
 */
class SponsorshipInviteFactory extends Factory
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
            'scope' => SponsorshipScope::CreationFee,
            'amount' => fake()->numberBetween(10, 200),
            'status' => SponsorshipInviteStatus::Pending,
            'token' => (string) Str::ulid(),
            'expires_at' => now()->addDays(3),
        ];
    }

    /**
     * @return Factory<SponsorshipInvite>
     */
    public function paid(): Factory
    {
        return $this->state([
            'sponsor_id' => User::factory(),
            'status' => SponsorshipInviteStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
