<?php

namespace App\Notifications;

use App\Models\GameSession;
use App\Models\Round;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\Channels\InAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class RoundCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly GameSession $gameSession,
        public readonly Round $round,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class, InAppChannel::class];
    }

    public function toFcm(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withNotification(FcmNotification::create(
                'Round completed',
                "Round {$this->round->number} has ended.",
            ))
            ->withData([
                'type' => 'round.completed',
                'game_session_id' => (string) $this->gameSession->id,
                'round_id' => (string) $this->round->id,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInApp(object $notifiable): array
    {
        return [
            'title' => 'Round completed',
            'body' => "Round {$this->round->number} has ended.",
            'type' => 'round.completed',
            'metadata' => [
                'game_session_id' => $this->gameSession->id,
                'round_id' => $this->round->id,
            ],
        ];
    }
}
