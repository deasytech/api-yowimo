<?php

namespace App\Notifications;

use App\Models\GameSession;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\Channels\InAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class GameCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly GameSession $gameSession,
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
                'Game completed',
                'The game has ended. Thanks for playing!',
            ))
            ->withData([
                'type' => 'game.completed',
                'game_session_id' => (string) $this->gameSession->id,
                'party_id' => (string) $this->gameSession->party_id,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInApp(object $notifiable): array
    {
        return [
            'title' => 'Game completed',
            'body' => 'The game has ended. Thanks for playing!',
            'type' => 'game.completed',
            'metadata' => [
                'game_session_id' => $this->gameSession->id,
                'party_id' => $this->gameSession->party_id,
            ],
        ];
    }
}
