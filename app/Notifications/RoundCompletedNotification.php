<?php

namespace App\Notifications;

use App\Models\GameSession;
use App\Models\Round;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class RoundCompletedNotification extends Notification implements ShouldQueue
{
    use DeliversViaFcmAndInApp, Queueable;

    public function __construct(
        public readonly GameSession $gameSession,
        public readonly Round $round,
    ) {}

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
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
