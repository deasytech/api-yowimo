<?php

namespace App\Notifications;

use App\Models\GameSession;
use App\Notifications\Channels\FcmChannel;
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
        return [FcmChannel::class];
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
}
