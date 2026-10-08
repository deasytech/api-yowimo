<?php

namespace App\Listeners;

use App\Enums\GameSessionStatus;
use App\Events\GameCompleted;
use App\Models\GameSession;
use App\Models\Turn;
use App\Models\User;
use App\Services\Referrals\ReferralService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Pays out the referral reward the moment a referred user completes their
 * first game — the same "first completed game" gate EvaluateGameCompletionBadges
 * uses for the first_party badge, so a referred user who never actually plays
 * can't trigger a payout just by signing up.
 */
class GrantReferralReward implements ShouldQueue
{
    public function __construct(private readonly ReferralService $referrals) {}

    public function handle(GameCompleted $event): void
    {
        $gameSession = GameSession::find($event->gameSessionId);

        if (! $gameSession) {
            return;
        }

        $userIds = Turn::where('game_session_id', $gameSession->id)->distinct()->pluck('user_id');

        $referredUsers = User::whereIn('id', $userIds)
            ->whereNotNull('referred_by_user_id')
            ->get();

        foreach ($referredUsers as $user) {
            $completedGameSessionCount = Turn::where('user_id', $user->id)
                ->whereHas('gameSession', fn ($query) => $query->where('status', GameSessionStatus::Completed))
                ->distinct('game_session_id')
                ->count('game_session_id');

            if ($completedGameSessionCount === 1) {
                $this->referrals->payoutFor($user);
            }
        }
    }
}
