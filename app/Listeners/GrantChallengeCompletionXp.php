<?php

namespace App\Listeners;

use App\Enums\XpTransactionType;
use App\Events\TurnCompleted;
use App\Models\Turn;
use App\Services\Game\XpService;

/**
 * Deliberately not ShouldQueue: TurnCompleted and GameCompleted are both
 * ShouldDispatchAfterCommit, dispatched in that order from the same
 * GameSessionService::advance() call. Running this listener inline guarantees
 * every turn's Challenge Completed XP is committed before GameCompleted (and
 * therefore GrantMvpBonus, which reads the same ledger) can even be
 * dispatched — with maxProcesses > 1 on Horizon's default queue (see
 * config/horizon.php), a queued version of this listener could race a
 * concurrently-running GrantMvpBonus and lose.
 */
class GrantChallengeCompletionXp
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
