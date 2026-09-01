<?php

namespace App\Listeners;

use App\Enums\XpTransactionType;
use App\Events\GameCompleted;
use App\Models\GameSession;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\Game\XpService;
use Illuminate\Contracts\Queue\ShouldQueue;

class GrantMvpBonus implements ShouldQueue
{
    private const XP_AMOUNT = 100;

    public function __construct(private readonly XpService $xp) {}

    public function handle(GameCompleted $event): void
    {
        $gameSession = GameSession::find($event->gameSessionId);

        if (! $gameSession) {
            return;
        }

        $totals = XpTransaction::query()
            ->where('game_session_id', $gameSession->id)
            ->selectRaw('user_id, SUM(amount) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        if ($totals->isEmpty()) {
            return;
        }

        $highest = $totals->max();
        $topScorerIds = $totals->filter(fn ($total) => $total === $highest)->keys();

        $users = User::whereIn('id', $topScorerIds)->get();

        foreach ($users as $user) {
            $this->xp->credit(
                $user,
                self::XP_AMOUNT,
                XpTransactionType::MvpBonus,
                reference: $gameSession,
                gameSessionId: $gameSession->id,
                idempotencyKey: "mvp-bonus:{$gameSession->id}:{$user->id}",
            );
        }
    }
}
