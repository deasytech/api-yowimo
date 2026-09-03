<?php

namespace App\Notifications;

use App\Models\Badge;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\Channels\InAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class BadgeAwardedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Badge $badge) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class, InAppChannel::class];
    }

    public function toFcm(object $notifiable): CloudMessage
    {
        $payload = $this->payload();

        return CloudMessage::new()
            ->withNotification(FcmNotification::create($payload['title'], $payload['body']))
            ->withData(['type' => $payload['type'], ...array_map('strval', $payload['metadata'])]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInApp(object $notifiable): array
    {
        return $this->payload();
    }

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
