<?php

namespace App\Notifications;

use App\Models\Badge;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class BadgeAwardedNotification extends Notification implements ShouldQueue
{
    use DeliversViaFcmAndInApp, Queueable;

    public function __construct(public readonly Badge $badge) {}

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'title' => 'Badge earned!',
            'body' => "You earned the \"{$this->badge->name}\" badge.",
            'type' => 'reward.badge.awarded',
            'metadata' => [
                'badge_id' => $this->badge->id,
                'badge_key' => $this->badge->key->value,
            ],
        ];
    }
}
