<?php

namespace Database\Factories;

use App\Models\WebhookEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'provider' => 'clerk',
            'event_id' => 'msg_'.$this->faker->unique()->bothify('??????????????????'),
            'event_type' => 'user.created',
            'payload' => [],
            'processed_at' => now(),
        ];
    }
}
