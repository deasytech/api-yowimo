<?php

namespace App\Services\Game;

use App\Enums\GameSessionStatus;
use App\Enums\VoteCategory;
use App\Enums\XpTransactionType;
use App\Events\VoteCast;
use App\Exceptions\Api\DuplicateVoteException;
use App\Exceptions\Api\VotingNotAllowedException;
use App\Models\Turn;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VoteService
{
    /**
     * XP awarded to the turn's player per vote received, by category.
     *
     * @var array<string, int>
     */
    private const XP_BY_CATEGORY = [
        'winner' => 25,
        'funny' => 15,
        'creativity' => 20,
    ];

    /**
     * @var array<string, XpTransactionType>
     */
    private const XP_TYPE_BY_CATEGORY = [
        'winner' => XpTransactionType::TurnWinnerVote,
        'funny' => XpTransactionType::TurnFunnyVote,
        'creativity' => XpTransactionType::TurnCreativityVote,
    ];

    public function __construct(private readonly XpService $xp) {}

    /**
     * Cast a vote on a completed turn, crediting XP to the turn's player.
     *
     * @throws VotingNotAllowedException if the turn hasn't completed, was AFK-skipped, or the game has already ended.
     * @throws DuplicateVoteException if the voter already cast this category of vote on this turn.
     */
    public function cast(User $voter, Turn $turn, VoteCategory $category): Vote
    {
        if ($turn->completed_at === null || $turn->is_afk) {
            throw new VotingNotAllowedException;
        }

        // Once the game has ended, GrantMvpBonus has already snapshotted final
        // standings; a vote afterward would credit XP that can never be
        // reflected in the MVP determination, so it's rejected rather than
        // silently accepted.
        if ($turn->gameSession->status !== GameSessionStatus::Running) {
            throw new VotingNotAllowedException('Voting is not allowed after the game has ended.');
        }

        return DB::transaction(function () use ($voter, $turn, $category) {
            try {
                $vote = Vote::create([
                    'turn_id' => $turn->id,
                    'voter_id' => $voter->id,
                    'category' => $category,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isDuplicateVoteViolation($exception)) {
                    throw $exception;
                }

                throw new DuplicateVoteException;
            }

            $this->xp->credit(
                $turn->user,
                self::XP_BY_CATEGORY[$category->value],
                self::XP_TYPE_BY_CATEGORY[$category->value],
                reference: $turn,
                gameSessionId: $turn->game_session_id,
                idempotencyKey: "vote-xp:{$turn->id}:{$voter->id}:{$category->value}",
            );

            VoteCast::dispatch($turn->game_session_id, $turn->id, $voter->id, $turn->user_id, $category->value);

            return $vote;
        });
    }

    private function isDuplicateVoteViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        // Matches both the MySQL/Postgres constraint name ("votes_turn_id_voter_id_category_unique")
        // and SQLite's literal column list ("votes.turn_id, votes.voter_id, votes.category").
        $matchesColumns = Str::contains($message, 'turn_id') && Str::contains($message, 'voter_id') && Str::contains($message, 'category');
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $matchesColumns && $isUniqueViolation;
    }
}
