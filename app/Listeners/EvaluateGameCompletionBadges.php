<?php

namespace App\Listeners;

use App\Enums\BadgeKey;
use App\Enums\GameSessionStatus;
use App\Events\GameCompleted;
use App\Models\GameSession;
use App\Models\Turn;
use App\Models\User;
use App\Services\Game\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EvaluateGameCompletionBadges implements ShouldQueue
{
    private const HUNDRED_PARTIES_THRESHOLD = 100;

    public function __construct(private readonly BadgeService $badges) {}

    public function handle(GameCompleted $event): void
    {
        $gameSession = GameSession::find($event->gameSessionId);

        if (! $gameSession) {
            return;
        }

        $turns = Turn::where('game_session_id', $gameSession->id)->get();
        $userIds = $turns->pluck('user_id')->unique();
        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        foreach ($userIds as $userId) {
            $user = $users->get($userId);

            if (! $user) {
                continue;
            }

            $completedGameSessionCount = Turn::where('user_id', $userId)
                ->whereHas('gameSession', fn ($query) => $query->where('status', GameSessionStatus::Completed))
                ->distinct('game_session_id')
                ->count('game_session_id');

            if ($completedGameSessionCount >= 1) {
                $this->badges->award($user, BadgeKey::FirstParty, $gameSession);
            }

            if ($completedGameSessionCount >= self::HUNDRED_PARTIES_THRESHOLD) {
                $this->badges->award($user, BadgeKey::HundredParties, $gameSession);
            }

            $hadAfkSkip = $turns->where('user_id', $userId)->contains('is_afk', true);

            if (! $hadAfkSkip) {
                $this->badges->award($user, BadgeKey::PerfectGame, $gameSession);
            }
        }
    }
}
