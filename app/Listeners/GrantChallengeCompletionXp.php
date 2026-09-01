<?php

namespace App\Listeners;

use App\Enums\XpTransactionType;
use App\Events\TurnCompleted;
use App\Models\Turn;
use App\Services\Game\XpService;
use Illuminate\Contracts\Queue\ShouldQueue;

class GrantChallengeCompletionXp implements ShouldQueue
{
    private const XP_AMOUNT = 50;

    public function __construct(private readonly XpService $xp) {}

    public function handle(TurnCompleted $event): void
    {
        if ($event->isAfk) {
            return;
        }

        $turn = Turn::find($event->turnId);

        if (! $turn) {
            return;
        }

        $this->xp->credit(
            $turn->user,
            self::XP_AMOUNT,
            XpTransactionType::ChallengeCompleted,
            reference: $turn,
            gameSessionId: $turn->game_session_id,
            idempotencyKey: "challenge-completed-xp:{$turn->id}",
        );
    }
}
