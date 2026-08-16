<?php

namespace App\Services\Game;

use App\Enums\GameSessionStatus;
use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Events\GameCompleted;
use App\Events\RoundCompleted;
use App\Exceptions\Api\GameSessionAlreadyActiveException;
use App\Exceptions\Api\GameSessionNotActiveException;
use App\Exceptions\Api\GameSessionPackUnavailableException;
use App\Exceptions\Api\InvalidPartyTransitionException;
use App\Jobs\SkipAfkTurn;
use App\Models\GameSession;
use App\Models\PackCard;
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
     * Seconds a player has to act on their turn before it's auto-skipped as AFK.
     */
    public const TURN_TIMEOUT_SECONDS = 30;

    private const DEFAULT_ROUNDS_COUNT = 10;

    /**
     * @throws InvalidPartyTransitionException if the party isn't live.
     * @throws GameSessionAlreadyActiveException if the party already has a running session.
     * @throws GameSessionPackUnavailableException if the party has no pack, or the pack has no cards.
     */
    public function start(User $host, Party $party, ?int $roundsCount = null): GameSession
    {
        return DB::transaction(function () use ($host, $party, $roundsCount) {
            $party = Party::query()->whereKey($party->id)->lockForUpdate()->firstOrFail();

            if ($party->status !== PartyStatus::Live) {
                throw new InvalidPartyTransitionException('The party must be live to start a game.');
            }

            if (GameSession::query()->where('party_id', $party->id)->where('status', GameSessionStatus::Running)->exists()) {
                throw new GameSessionAlreadyActiveException;
            }

            if (! $party->pack_id) {
                throw new GameSessionPackUnavailableException('This party has no pack assigned.');
            }

            $turnOrder = PartyMember::query()->where('party_id', $party->id)->pluck('user_id')->shuffle()->values()->all();

            $session = GameSession::create([
                'party_id' => $party->id,
                'host_id' => $host->id,
                'pack_id' => $party->pack_id,
                'status' => GameSessionStatus::Running,
                'rounds_count' => $roundsCount ?? self::DEFAULT_ROUNDS_COUNT,
                'current_round_number' => 1,
                'turn_order' => $turnOrder,
                'current_turn_index' => 0,
                'started_at' => now(),
            ]);

            $round = Round::create([
                'game_session_id' => $session->id,
                'number' => 1,
                'started_at' => now(),
            ]);

            $this->dealTurn($session, $round, 0, $turnOrder[0]);

            return $session;
        });
    }

    /**
     * Complete the current turn and deal the next one, advancing the round/session as needed.
     *
     * @throws GameSessionNotActiveException if the session isn't running.
     * @throws GameSessionPackUnavailableException if the pack runs out of cards of the needed kind.
     */
    public function nextTurn(GameSession $session): GameSession
    {
        return DB::transaction(function () use ($session) {
            $session = GameSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();

            if ($session->status !== GameSessionStatus::Running) {
                throw new GameSessionNotActiveException;
            }

            $turn = $session->currentTurn();
            $turn->update(['completed_at' => now()]);

            return $this->advance($session);
        });
    }

    /**
     * Called by the delayed timer job (and the crash-recovery sweep) once a turn's
     * timer expires. No-ops if the turn was already completed by the time it runs
     * (host already advanced normally, or a previous run already handled it), or if
     * the timer genuinely hasn't elapsed yet — the `sync` queue driver ignores
     * `->delay()` and runs jobs immediately, so this guard is what makes the AFK
     * skip correct regardless of queue driver, not just a testing workaround.
     */
    public function skipAfkTurn(int $turnId): ?GameSession
    {
        return DB::transaction(function () use ($turnId) {
            $turn = Turn::query()->whereKey($turnId)->lockForUpdate()->first();

            if (! $turn || $turn->completed_at !== null) {
                return null;
            }

            if (now()->lessThan($turn->started_at->copy()->addSeconds(self::TURN_TIMEOUT_SECONDS))) {
                return null;
            }

            $session = GameSession::query()->whereKey($turn->game_session_id)->lockForUpdate()->firstOrFail();

            if ($session->status !== GameSessionStatus::Running || $session->currentTurn()?->id !== $turn->id) {
                return null;
            }

            $turn->update(['completed_at' => now(), 'is_afk' => true]);

            return $this->advance($session);
        });
    }

    /**
     * Advance from the just-completed current turn: deal the next turn, or complete
     * the round/session if that was the last turn of the round/game.
     */
    private function advance(GameSession $session): GameSession
    {
        $round = $session->currentRound();
        $turnOrder = $session->turn_order;
        $nextIndex = $session->current_turn_index + 1;

        if ($nextIndex < count($turnOrder)) {
            $session->update(['current_turn_index' => $nextIndex]);
            $this->dealTurn($session, $round, $nextIndex, $turnOrder[$nextIndex]);

            return $session->fresh();
        }

        $round->update(['completed_at' => now()]);
        RoundCompleted::dispatch($session->id, $round->id, $round->number);

        if ($session->current_round_number >= $session->rounds_count) {
            $session->update([
                'status' => GameSessionStatus::Completed,
                'ended_at' => now(),
            ]);
            GameCompleted::dispatch($session->id, $session->party_id);

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
            'current_turn_index' => 0,
        ]);

        $this->dealTurn($session, $newRound, 0, $turnOrder[0]);

        return $session->fresh();
    }

    private function dealTurn(GameSession $session, Round $round, int $position, int $userId): Turn
    {
        $turnsSoFar = Turn::query()->where('game_session_id', $session->id)->count();
        $kind = $turnsSoFar % 2 === 0 ? PackCardKind::Truth : PackCardKind::Dare;

        $card = $this->selectCard($session, $kind);

        $turn = Turn::create([
            'game_session_id' => $session->id,
            'round_id' => $round->id,
            'user_id' => $userId,
            'pack_card_id' => $card->id,
            'position' => $position,
            'started_at' => now(),
        ]);

        SkipAfkTurn::dispatch($turn->id)
            ->delay(now()->addSeconds(self::TURN_TIMEOUT_SECONDS))
            ->afterCommit();

        return $turn;
    }

    /**
     * Crash-recovery safety net: re-processes any turn whose timer has expired but
     * that's still open, in case its delayed queue job was lost (e.g. a Redis
     * restart) rather than merely delayed. Idempotent — skipAfkTurn() no-ops for
     * turns already completed by the time it runs.
     */
    public function sweepExpiredTurns(): int
    {
        $expiredTurnIds = Turn::query()
            ->whereNull('completed_at')
            ->where('started_at', '<=', now()->subSeconds(self::TURN_TIMEOUT_SECONDS))
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
     * @throws GameSessionPackUnavailableException if the pack has no cards of the requested kind.
     */
    private function selectCard(GameSession $session, PackCardKind $kind): PackCard
    {
        $usedCardIds = Turn::query()->where('game_session_id', $session->id)->pluck('pack_card_id');

        $card = PackCard::query()
            ->where('pack_id', $session->pack_id)
            ->where('kind', $kind)
            ->whereNotIn('id', $usedCardIds)
            ->inRandomOrder()
            ->first();

        // Every unused card of this kind has been dealt already this session — reshuffle and allow repeats.
        $card ??= PackCard::query()
            ->where('pack_id', $session->pack_id)
            ->where('kind', $kind)
            ->inRandomOrder()
            ->first();

        if (! $card) {
            throw new GameSessionPackUnavailableException("This party's pack has no {$kind->value} cards available to deal.");
        }

        return $card;
    }
}
