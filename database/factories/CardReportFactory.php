<?php

namespace Database\Factories;

use App\Enums\CardReportReason;
use App\Models\CardReport;
use App\Models\PackCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CardReport>
 */
class CardReportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pack_card_id' => PackCard::factory(),
            'reporter_id' => User::factory(),
            'reason' => $this->faker->randomElement(CardReportReason::cases()),
            'note' => $this->faker->optional()->sentence(),
        ];
    }
}
