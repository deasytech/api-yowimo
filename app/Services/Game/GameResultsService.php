<?php

namespace App\Services\Game;

use App\Enums\VoteCategory;
use App\Enums\XpTransactionType;
use App\Models\GameSession;
use App\Models\Turn;
use App\Models\User;
use App\Models\Vote;
use App\Models\XpTransaction;
use Illuminate\Support\Collection;

class GameResultsService
{
    /**
     * Per-player standings for a session, highest XP first: XP earned in this
     * session, votes received by category, turn outcomes, and whether they
     * were MVP. Works mid-game (a live scoreboard) as well as after it; MVP is
     * only decided once the game completes. Four grouped queries in total,
     * regardless of the number of players.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function standings(GameSession $session): Collection
    {
        $xpByUser = XpTransaction::query()
            ->where('game_session_id', $session->id)
            ->selectRaw('user_id, SUM(amount) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $mvpUserIds = XpTransaction::query()
            ->where('game_session_id', $session->id)
            ->where('type', XpTransactionType::MvpBonus)
            ->pluck('user_id')
            ->all();

        $votes = Vote::query()
            ->join('turns', 'turns.id', '=', 'votes.turn_id')
            ->where('turns.game_session_id', $session->id)
            ->selectRaw('turns.user_id as user_id, votes.category as category, COUNT(*) as total')
            ->groupBy('turns.user_id', 'votes.category')
            ->get()
            ->groupBy('user_id');

        $turns = Turn::query()
            ->where('game_session_id', $session->id)
            ->get(['user_id', 'completed_at', 'is_afk', 'is_skipped'])
            ->groupBy('user_id');

        $userIds = collect($session->turn_order)->merge($turns->keys())->unique()->values();
        $users = User::withTrashed()->whereIn('id', $userIds)->get()->keyBy('id');

        return $userIds
            ->filter(fn ($userId) => $users->has($userId))
            ->map(fn ($userId) => [
                'user' => $users->get($userId),
                'xp' => (int) ($xpByUser[$userId] ?? 0),
                'votes' => $this->voteCounts($votes->get($userId, collect())),
                'turns' => $this->turnCounts($turns->get($userId, collect())),
                'is_mvp' => in_array($userId, $mvpUserIds, true),
            ])
            ->sortByDesc('xp')
            ->values();
    }

    /**
     * @param  Collection<int, Vote>  $votes
     * @return array<string, int>
     */
    private function voteCounts(Collection $votes): array
    {
        $counts = [];

        foreach (VoteCategory::cases() as $category) {
            $row = $votes->first(fn ($vote) => ($vote->category instanceof VoteCategory ? $vote->category : VoteCategory::from($vote->category)) === $category);
            $counts[$category->value] = (int) ($row?->total ?? 0);
        }

        return $counts;
    }

    /**
     * @param  Collection<int, Turn>  $turns
     * @return array<string, int>
     */
    private function turnCounts(Collection $turns): array
    {
        $finished = $turns->whereNotNull('completed_at');

        return [
            'completed' => $finished->where('is_afk', false)->where('is_skipped', false)->count(),
            'skipped' => $finished->where('is_skipped', true)->count(),
            'afk' => $finished->where('is_afk', true)->count(),
        ];
    }
}
