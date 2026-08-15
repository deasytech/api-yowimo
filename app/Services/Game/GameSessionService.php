<?php

namespace App\Services\Game;

use App\Enums\GameSessionStatus;
use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Exceptions\Api\GameSessionAlreadyActiveException;
use App\Exceptions\Api\GameSessionNotActiveException;
use App\Exceptions\Api\GameSessionPackUnavailableException;
use App\Exceptions\Api\InvalidPartyTransitionException;
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

            $round = $session->currentRound();
            $turn = $session->currentTurn();
            $turn->update(['completed_at' => now()]);

            $turnOrder = $session->turn_order;
            $nextIndex = $session->current_turn_index + 1;

            if ($nextIndex < count($turnOrder)) {
                $session->update(['current_turn_index' => $nextIndex]);
                $this->dealTurn($session, $round, $nextIndex, $turnOrder[$nextIndex]);

                return $session->fresh();
            }

            $round->update(['completed_at' => now()]);

            if ($session->current_round_number >= $session->rounds_count) {
                $session->update([
                    'status' => GameSessionStatus::Completed,
                    'ended_at' => now(),
                ]);

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
        });
    }

    private function dealTurn(GameSession $session, Round $round, int $position, int $userId): Turn
    {
        $turnsSoFar = Turn::query()->where('game_session_id', $session->id)->count();
        $kind = $turnsSoFar % 2 === 0 ? PackCardKind::Truth : PackCardKind::Dare;

        $card = $this->selectCard($session, $kind);

        return Turn::create([
            'game_session_id' => $session->id,
            'round_id' => $round->id,
            'user_id' => $userId,
            'pack_card_id' => $card->id,
            'position' => $position,
            'started_at' => now(),
        ]);
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
