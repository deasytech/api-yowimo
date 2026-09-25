<?php

namespace App\Notifications;

use App\Models\GameSession;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GameCompletedNotification extends Notification implements ShouldQueue
{
    use DeliversViaFcmAndInApp, Queueable;

    public function __construct(
        public readonly GameSession $gameSession,
    ) {}

    /**
     * @return array<string, mixed>
     */
    private function payload(): array // NOSONAR php:S1144 - satisfies DeliversViaFcmAndInApp::payload(), called via $this->payload() in the trait
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
