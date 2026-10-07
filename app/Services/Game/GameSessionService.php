<?php

namespace App\Services\Game;

use App\Enums\GameSessionStatus;
use App\Enums\PackCardKind;
use App\Enums\PartyMemberStatus;
use App\Enums\PartyStatus;
use App\Events\GameCompleted;
use App\Events\GameEnded;
use App\Events\GamePaused;
use App\Events\GameResumed;
use App\Events\GameStarted;
use App\Events\GameVotingStarted;
use App\Events\RoundCompleted;
use App\Events\TurnCompleted;
use App\Events\TurnStarted;
use App\Exceptions\Api\GameSessionAlreadyActiveException;
use App\Exceptions\Api\GameSessionNotActiveException;
use App\Exceptions\Api\GameSessionPackUnavailableException;
use App\Exceptions\Api\InvalidPartyTransitionException;
use App\Exceptions\Api\TurnNotActiveException;
use App\Jobs\FinishGameVoting;
use App\Jobs\SkipAfkTurn;
use App\Models\GameSession;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\PackPurchase;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\Round;
use App\Models\Turn;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GameSessionService
{
    /**
     * Rounds counts the host is allowed to configure a session for.
     *
     * @var array<int, int>
     */
    public const ALLOWED_ROUNDS_COUNTS = [5, 10, 15, 20];

    /**
     * Turn timer lengths (seconds) the host is allowed to configure a session for.
     *
     * @var array<int, int>
     */
    public const ALLOWED_TURN_SECONDS = [15, 30, 45, 60, 90];

    /**
     * Default seconds a player has to act on their turn before it's auto-skipped as AFK.
     */
    public const TURN_TIMEOUT_SECONDS = 30;

    /**
     * Seconds players have to vote on the final turn before the game completes.
     */
    public const VOTING_WINDOW_SECONDS = 30;

    /**
     * Statuses of a session that hasn't finished yet.
     *
     * @var array<int, GameSessionStatus>
     */
    public const IN_PROGRESS_STATUSES = [GameSessionStatus::Running, GameSessionStatus::Paused, GameSessionStatus::Voting];

    private const DEFAULT_ROUNDS_COUNT = 10;

    /**
     * @throws InvalidPartyTransitionException if the party isn't live.
     * @throws GameSessionAlreadyActiveException if the party already has a session in progress.
     * @throws GameSessionPackUnavailableException if the party has no pack, or the pack has no cards.
     */
    public function start(User $host, Party $party, ?int $roundsCount = null, ?int $turnSeconds = null): GameSession
    {
        return DB::transaction(function () use ($host, $party, $roundsCount, $turnSeconds) {
            $party = Party::query()->whereKey($party->id)->lockForUpdate()->firstOrFail();

            if ($party->status !== PartyStatus::Live) {
                throw new InvalidPartyTransitionException('The party must be live to start a game.');
            }

            $inProgressId = GameSession::query()
                ->where('party_id', $party->id)
                ->whereIn('status', self::IN_PROGRESS_STATUSES)
                ->value('id');

            if ($inProgressId) {
                throw new GameSessionAlreadyActiveException(gameSessionId: $inProgressId);
            }

            if (! $party->pack_id) {
                throw new GameSessionPackUnavailableException('This party has no pack assigned.');
            }

            if (! $this->hasPlayableCards($party->pack_id, $host->id)) {
                throw new GameSessionPackUnavailableException('This pack has no playable cards.');
            }

            // Guests (no user_id) sit in the room but have no account to
            // record a digital turn against, so they're excluded here.
            $turnOrder = PartyMember::query()
                ->where('party_id', $party->id)
                ->where('status', PartyMemberStatus::Active)
                ->whereNotNull('user_id')
                ->pluck('user_id')
                ->shuffle()
                ->values()
                ->all();

            $session = GameSession::create([
                'party_id' => $party->id,
                'host_id' => $host->id,
                'pack_id' => $party->pack_id,
                'status' => GameSessionStatus::Running,
                'rounds_count' => $roundsCount ?? self::DEFAULT_ROUNDS_COUNT,
                'current_round_number' => 1,
                'turn_order' => $turnOrder,
                'current_turn_index' => 0,
                'turn_seconds' => $turnSeconds ?? self::TURN_TIMEOUT_SECONDS,
                'started_at' => now(),
            ]);

            $round = Round::create([
                'game_session_id' => $session->id,
                'number' => 1,
                'started_at' => now(),
            ]);

            GameStarted::dispatch($session->id, $party->id);

            if (! $this->dealTurn($session, $round, 0)) {
                throw new GameSessionPackUnavailableException('This pack has no playable cards.');
            }

            return $session;
        });
    }

    /**
     * Host action: complete the current turn and deal the next one, advancing the round/session as needed.
     *
     * @throws GameSessionNotActiveException if the session isn't running.
     */
    public function nextTurn(GameSession $session): GameSession
    {
        return DB::transaction(function () use ($session) {
            $session = $this->lockRunning($session->id);

            $turn = $session->currentTurn();

            if ($turn && $turn->completed_at === null) {
                $this->closeTurn($session, $turn);
            }

            return $this->advance($session, $session->current_turn_index + 1);
        });
    }

    /**
     * Player (or host) action on a specific turn: mark it done, or skip it
     * (no challenge XP, can't be voted on). The turn must still be the open,
     * current one, so a stale client can't complete a turn twice.
     *
     * @throws GameSessionNotActiveException if the session isn't running.
     * @throws TurnNotActiveException if the turn isn't the open, current turn.
     */
    public function completeTurn(GameSession $session, Turn $turn, bool $skipped = false): GameSession
    {
        return DB::transaction(function () use ($session, $turn, $skipped) {
            $session = $this->lockRunning($session->id);

            $current = $session->currentTurn();

            if (! $current || $current->id !== $turn->id || $current->completed_at !== null) {
                throw new TurnNotActiveException;
            }

            $this->closeTurn($session, $current, skipped: $skipped);

            return $this->advance($session, $session->current_turn_index + 1);
        });
    }

    /**
     * Called by the delayed timer job (and the crash-recovery sweep) once a turn's
     * timer expires. No-ops if the turn was already completed by the time it runs
     * (host already advanced normally, or a previous run already handled it), if
     * the game is paused/finished, or if the timer genuinely hasn't elapsed yet —
     * the `sync` queue driver ignores `->delay()` and runs jobs immediately, and a
     * resume moves `expires_at` later, so this guard is what makes the AFK skip
     * correct regardless of queue driver, not just a testing workaround.
     */
    public function skipAfkTurn(int $turnId): ?GameSession
    {
        return DB::transaction(function () use ($turnId) {
            // Lock the session before the turn — the same order as every other
            // session operation (which lock the session, then write the turn),
            // so a concurrent complete/next-turn can't deadlock with this.
            $sessionId = Turn::query()->whereKey($turnId)->value('game_session_id');

            if (! $sessionId) {
                return null;
            }

            $session = GameSession::query()->whereKey($sessionId)->lockForUpdate()->firstOrFail();
            $turn = Turn::query()->whereKey($turnId)->lockForUpdate()->first();

            if (! $turn || $turn->completed_at !== null || now()->lessThan($turn->expires_at)) {
                return null;
            }

            if ($session->status !== GameSessionStatus::Running || $session->currentTurn()?->id !== $turn->id) {
                return null;
            }

            $this->closeTurn($session, $turn, afk: true);

            return $this->advance($session, $session->current_turn_index + 1);
        });
    }

    /**
     * Host action: freeze the game, including the current turn's timer.
     *
     * @throws GameSessionNotActiveException if the session isn't running.
     */
    public function pause(GameSession $session): GameSession
    {
        return DB::transaction(function () use ($session) {
            $session = $this->lockRunning($session->id, 'Only a running game can be paused.');

            $turn = $session->currentTurn();
            $remaining = $turn && $turn->completed_at === null
                ? max(0, (int) ceil(now()->diffInSeconds($turn->expires_at, false)))
                : null;

            $session->update([
                'status' => GameSessionStatus::Paused,
                'paused_at' => now(),
                'paused_turn_remaining_seconds' => $remaining,
            ]);

            GamePaused::dispatch($session->id, $remaining);

            return $session->fresh();
        });
    }

    /**
     * Host action: unfreeze the game, giving the current turn the time it had left when paused.
     *
     * @throws GameSessionNotActiveException if the session isn't paused.
     */
    public function resume(GameSession $session): GameSession
    {
        return DB::transaction(function () use ($session) {
            $session = GameSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($session->status !== GameSessionStatus::Paused) {
                throw new GameSessionNotActiveException('This game is not paused.');
            }

            $remaining = $session->paused_turn_remaining_seconds ?? $session->turn_seconds;

            $session->update([
                'status' => GameSessionStatus::Running,
                'paused_at' => null,
                'paused_turn_remaining_seconds' => null,
            ]);

            $turn = $session->currentTurn();

            // The current player left while the game was paused: their turn was
            // closed then, so move straight on to the next player.
            if (! $turn || $turn->completed_at !== null) {
                GameResumed::dispatch($session->id, null, null);

                return $this->advance($session, $session->current_turn_index + 1);
            }

            $turn->update(['expires_at' => now()->addSeconds($remaining)]);
            $this->scheduleAfkSkip($turn);

            GameResumed::dispatch($session->id, $turn->id, $turn->expires_at->toIso8601String());

            return $session->fresh();
        });
    }

    /**
     * Stop any unfinished game for the party (the host ended the party mid-game).
     * Unlike completion, no GameCompleted follows — so no completion reward,
     * MVP, badges or AI recap — but XP already earned from turns/votes stays.
     */
    public function endForParty(Party $party): void
    {
        DB::transaction(function () use ($party) {
            $sessions = GameSession::query()
                ->where('party_id', $party->id)
                ->whereIn('status', self::IN_PROGRESS_STATUSES)
                ->lockForUpdate()
                ->get();

            foreach ($sessions as $session) {
                $this->endSession($session);
            }
        });
    }

    /**
     * A player left the party: if it's currently their turn in an unfinished
     * game, close it as skipped and (unless paused) move on. Their later turns
     * are skipped automatically when dealt (see nextActiveIndex()).
     */
    public function handlePlayerLeft(Party $party, int $userId): void
    {
        DB::transaction(function () use ($party, $userId) {
            $session = $this->lockInProgressForParty($party, [GameSessionStatus::Running, GameSessionStatus::Paused]);

            $turn = $session?->currentTurn();

            if (! $turn || $turn->completed_at !== null || $turn->user_id !== $userId) {
                return;
            }

            $this->closeTurn($session, $turn, skipped: true);

            if ($session->status === GameSessionStatus::Running) {
                $this->advance($session, $session->current_turn_index + 1);
            }
        });
    }

    /**
     * A player joined (or rejoined) the party mid-game: someone not yet in the
     * turn order is appended to the end of it, so they play from their first
     * reachable position. A rejoining player keeps their original position.
     */
    public function handlePlayerJoined(Party $party, int $userId): void
    {
        DB::transaction(function () use ($party, $userId) {
            $session = $this->lockInProgressForParty($party, [GameSessionStatus::Running, GameSessionStatus::Paused]);

            if (! $session || in_array($userId, $session->turn_order, true)) {
                return;
            }

            $session->update(['turn_order' => [...$session->turn_order, $userId]]);
        });
    }

    /**
     * Close the final voting window and complete the game. Called by the
     * delayed FinishGameVoting job and the crash-recovery sweep; no-ops if
     * the session isn't voting or the window hasn't elapsed yet.
     */
    public function finishVoting(int $sessionId): ?GameSession
    {
        return DB::transaction(function () use ($sessionId) {
            $session = GameSession::query()->whereKey($sessionId)->lockForUpdate()->first();

            if (! $session
                || $session->status !== GameSessionStatus::Voting
                || now()->lessThan($session->voting_ends_at)) {
                return null;
            }

            $this->completeSession($session);

            return $session->fresh();
        });
    }

    /**
     * Crash-recovery safety net: re-processes any turn whose timer has expired but
     * that's still open, in case its delayed queue job was lost (e.g. a Redis
     * restart) rather than merely delayed. Idempotent — skipAfkTurn() no-ops for
     * turns already completed, or in paused/finished games, by the time it runs.
     */
    public function sweepExpiredTurns(): int
    {
        $expiredTurnIds = Turn::query()
            ->whereNull('completed_at')
            ->where('expires_at', '<=', now())
            ->whereHas('gameSession', fn ($query) => $query->where('status', GameSessionStatus::Running))
            ->pluck('id');

        $skipped = 0;

        foreach ($expiredTurnIds as $turnId) {
            if ($this->skipAfkTurn($turnId) !== null) {
                $skipped++;
            }
        }

        return $skipped;
    }

    /**
     * Crash-recovery safety net for the final voting window, mirroring sweepExpiredTurns().
     */
    public function sweepExpiredVotingWindows(): int
    {
        $sessionIds = GameSession::query()
            ->where('status', GameSessionStatus::Voting)
            ->where('voting_ends_at', '<=', now())
            ->pluck('id');

        $completed = 0;

        foreach ($sessionIds as $sessionId) {
            if ($this->finishVoting($sessionId) !== null) {
                $completed++;
            }
        }

        return $completed;
    }

    /**
     * Advance to the first still-active player at or after `$fromIndex` in
     * the current round, or complete the round — and then either start the
     * next round, or open the final voting window after the last one.
     */
    private function advance(GameSession $session, int $fromIndex): GameSession
    {
        $round = $session->currentRound();
        $nextIndex = $this->nextActiveIndex($session, $fromIndex);

        if ($nextIndex !== null) {
            if ($this->dealTurn($session, $round, $nextIndex)) {
                $session->update(['current_turn_index' => $nextIndex]);

                return $session->fresh();
            }

            $this->completeSession($session);

            return $session->fresh();
        }

        $round->update(['completed_at' => now()]);
        RoundCompleted::dispatch($session->id, $round->id, $round->number);

        if ($session->current_round_number >= $session->rounds_count) {
            $this->beginVoting($session);

            return $session->fresh();
        }

        $firstIndex = $this->nextActiveIndex($session, 0);

        if ($firstIndex === null) {
            $this->endSession($session);

            return $session->fresh();
        }

        $nextRoundNumber = $session->current_round_number + 1;

        $newRound = Round::create([
            'game_session_id' => $session->id,
            'number' => $nextRoundNumber,
            'started_at' => now(),
        ]);

        $session->update([
            'current_round_number' => $nextRoundNumber,
            'current_turn_index' => $firstIndex,
        ]);

        if (! $this->dealTurn($session, $newRound, $firstIndex)) {
            $this->completeSession($session);
        }

        return $session->fresh();
    }

    /**
     * The first position at or after `$fromIndex` whose player is still an
     * active member of the party, so players who left are skipped over
     * instead of waiting out an AFK timer. Null if there's none left this round.
     */
    private function nextActiveIndex(GameSession $session, int $fromIndex): ?int
    {
        $activeUserIds = PartyMember::query()
            ->where('party_id', $session->party_id)
            ->where('status', PartyMemberStatus::Active)
            ->pluck('user_id')
            ->all();

        $turnOrder = $session->turn_order;
        $count = count($turnOrder);

        for ($index = $fromIndex; $index < $count; $index++) {
            if (in_array($turnOrder[$index], $activeUserIds, true)) {
                return $index;
            }
        }

        return null;
    }

    private function beginVoting(GameSession $session): void
    {
        $votingEndsAt = now()->addSeconds(self::VOTING_WINDOW_SECONDS);

        $session->update([
            'status' => GameSessionStatus::Voting,
            'voting_ends_at' => $votingEndsAt,
        ]);

        FinishGameVoting::dispatch($session->id)->delay($votingEndsAt)->afterCommit();

        GameVotingStarted::dispatch($session->id, $votingEndsAt->toIso8601String());
    }

    /**
     * Mark the session ended, closing its open turn/round without firing
     * TurnCompleted/RoundCompleted (nothing was actually completed).
     */
    private function endSession(GameSession $session): void
    {
        $turn = $session->currentTurn();
        $round = $session->currentRound();

        if ($turn && $turn->completed_at === null) {
            $turn->update(['completed_at' => now()]);
        }

        if ($round && $round->completed_at === null) {
            $round->update(['completed_at' => now()]);
        }

        $session->update([
            'status' => GameSessionStatus::Ended,
            'ended_at' => now(),
            'paused_at' => null,
            'paused_turn_remaining_seconds' => null,
        ]);

        GameEnded::dispatch($session->id, $session->party_id);
    }

    /**
     * Finish a game whose pack can no longer provide a card, without opening a
     * voting window for a turn that was never dealt.
     */
    private function completeSession(GameSession $session): void
    {
        $round = $session->currentRound();

        if ($round && $round->completed_at === null) {
            $round->update(['completed_at' => now()]);
        }

        $session->update([
            'status' => GameSessionStatus::Completed,
            'ended_at' => now(),
            'paused_at' => null,
            'paused_turn_remaining_seconds' => null,
        ]);

        GameCompleted::dispatch($session->id, $session->party_id);
    }

    private function closeTurn(GameSession $session, Turn $turn, bool $afk = false, bool $skipped = false): void
    {
        $turn->update(['completed_at' => now(), 'is_afk' => $afk, 'is_skipped' => $skipped]);

        TurnCompleted::dispatch($session->id, $turn->round_id, $turn->id, $turn->user_id, $afk, $skipped);
    }

    private function dealTurn(GameSession $session, Round $round, int $position): ?Turn
    {
        $turnsSoFar = Turn::query()->where('game_session_id', $session->id)->count();
        $kind = $turnsSoFar % 2 === 0 ? PackCardKind::Truth : PackCardKind::Dare;

        $card = $this->selectCard($session, $kind);

        if (! $card) {
            return null;
        }

        $turn = Turn::create([
            'game_session_id' => $session->id,
            'round_id' => $round->id,
            'user_id' => $session->turn_order[$position],
            'pack_card_id' => $card->id,
            'position' => $position,
            'started_at' => now(),
            'expires_at' => now()->addSeconds($session->turn_seconds),
        ]);

        $this->scheduleAfkSkip($turn);

        TurnStarted::dispatch(
            $session->id,
            $round->id,
            $turn->id,
            $turn->user_id,
            $position,
            $turn->expires_at->toIso8601String(),
            ['id' => $card->id, 'kind' => $card->kind->value, 'text' => $card->text, 'position' => $card->position],
        );

        return $turn;
    }

    private function scheduleAfkSkip(Turn $turn): void
    {
        SkipAfkTurn::dispatch($turn->id)
            ->delay($turn->expires_at)
            ->afterCommit();
    }

    /**
     * @throws GameSessionNotActiveException if the session isn't running.
     */
    private function lockRunning(int $sessionId, ?string $message = null): GameSession
    {
        $session = GameSession::query()->whereKey($sessionId)->lockForUpdate()->firstOrFail();

        if ($session->status !== GameSessionStatus::Running) {
            throw $message ? new GameSessionNotActiveException($message) : new GameSessionNotActiveException;
        }

        return $session;
    }

    /**
     * @param  array<int, GameSessionStatus>  $statuses
     */
    private function lockInProgressForParty(Party $party, array $statuses): ?GameSession
    {
        return GameSession::query()
            ->where('party_id', $party->id)
            ->whereIn('status', $statuses)
            ->lockForUpdate()
            ->latest('id')
            ->first();
    }

    /**
     * A paid pack the host hasn't bought is restricted to its is_preview
     * cards once gameplay actually deals them — mirrors the restriction
     * PackService::find() already applies to the pack detail view, which
     * otherwise has no effect on what a hosted party can actually play.
     */
    private function isCardSelectionRestricted(int $packId, int $hostId): bool
    {
        return Pack::query()->whereKey($packId)->value('price') > 0
            && PackPurchase::query()->where(['pack_id' => $packId, 'user_id' => $hostId])->doesntExist();
    }

    private function hasPlayableCards(int $packId, int $hostId): bool
    {
        $restricted = $this->isCardSelectionRestricted($packId, $hostId);

        return PackCard::query()
            ->where('pack_id', $packId)
            ->when($restricted, fn ($query) => $query->where('is_preview', true))
            ->lockForUpdate()
            ->first(['id']) !== null;
    }

    private function selectCard(GameSession $session, PackCardKind $kind): ?PackCard
    {
        $usedCardIds = Turn::query()->where('game_session_id', $session->id)->pluck('pack_card_id');
        $restricted = $this->isCardSelectionRestricted($session->pack_id, $session->host_id);

        $card = PackCard::query()
            ->where('pack_id', $session->pack_id)
            ->where('kind', $kind)
            ->when($restricted, fn ($query) => $query->where('is_preview', true))
            ->whereNotIn('id', $usedCardIds)
            ->inRandomOrder()
            ->first();

        // Prefer the alternating kind, but use any unused card before repeating one.
        $card ??= PackCard::query()
            ->where('pack_id', $session->pack_id)
            ->when($restricted, fn ($query) => $query->where('is_preview', true))
            ->whereNotIn('id', $usedCardIds)
            ->inRandomOrder()
            ->first();

        // The entire pack has been used. Keep the normal kind preference while
        // allowing truth-only and dare-only packs to continue playing.
        $card ??= PackCard::query()
            ->where('pack_id', $session->pack_id)
            ->where('kind', $kind)
            ->when($restricted, fn ($query) => $query->where('is_preview', true))
            ->inRandomOrder()
            ->first();

        return $card ?? PackCard::query()
            ->where('pack_id', $session->pack_id)
            ->when($restricted, fn ($query) => $query->where('is_preview', true))
            ->inRandomOrder()
            ->first();
    }
}
