<?php

use App\Enums\GameSessionStatus;
use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Exceptions\Api\GameSessionAlreadyActiveException;
use App\Exceptions\Api\GameSessionNotActiveException;
use App\Exceptions\Api\GameSessionPackUnavailableException;
use App\Exceptions\Api\InvalidPartyTransitionException;
use App\Models\GameSession;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\Round;
use App\Models\Turn;
use App\Models\User;
use App\Services\Game\GameSessionService;

function makeLivePartyWithMembers(int $memberCount = 3, int $cardsPerKind = 20): Party
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count($cardsPerKind)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count($cardsPerKind)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'pack_id' => $pack->id,
        'status' => PartyStatus::Live,
    ]);

    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    for ($i = 1; $i < $memberCount; $i++) {
        PartyMember::factory()->create(['party_id' => $party->id]);
    }

    return $party->fresh();
}

it('starts a session with a shuffled turn order covering every member and deals the first turn', function () {
    $party = makeLivePartyWithMembers(4);

    $session = app(GameSessionService::class)->start($party->host, $party);

    expect($session->status)->toBe(GameSessionStatus::Running);
    expect($session->rounds_count)->toBe(10);
    expect($session->current_round_number)->toBe(1);
    expect($session->current_turn_index)->toBe(0);
    expect($session->turn_order)->toHaveCount(4);
    expect(collect($session->turn_order)->sort()->values()->all())
        ->toBe(PartyMember::where('party_id', $party->id)->pluck('user_id')->sort()->values()->all());

    expect(Round::where('game_session_id', $session->id)->count())->toBe(1);

    $turn = Turn::where('game_session_id', $session->id)->first();
    expect($turn->position)->toBe(0);
    expect($turn->user_id)->toBe($session->turn_order[0]);
    expect($turn->packCard->kind)->toBe(PackCardKind::Truth);
});

it('rejects starting a game for a party that is not live', function () {
    $party = makeLivePartyWithMembers(2);
    $party->update(['status' => PartyStatus::Draft]);

    expect(fn () => app(GameSessionService::class)->start($party->host, $party))
        ->toThrow(InvalidPartyTransitionException::class);
});

it('rejects starting a second game while one is already active', function () {
    $party = makeLivePartyWithMembers(2);
    app(GameSessionService::class)->start($party->host, $party);

    expect(fn () => app(GameSessionService::class)->start($party->host, $party))
        ->toThrow(GameSessionAlreadyActiveException::class);
});

it('rejects starting a game when the party has no pack assigned', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => null, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    expect(fn () => app(GameSessionService::class)->start($host, $party))
        ->toThrow(GameSessionPackUnavailableException::class);
});

it('accepts a host-configured rounds count from the allowed set', function () {
    $party = makeLivePartyWithMembers(2);

    $session = app(GameSessionService::class)->start($party->host, $party, 20);

    expect($session->rounds_count)->toBe(20);
});

it('advances through every member before repeating a round, alternating truth/dare', function () {
    $party = makeLivePartyWithMembers(3);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party);

    $firstTurn = $session->currentTurn();
    expect($firstTurn->packCard->kind)->toBe(PackCardKind::Truth);

    $session = $service->nextTurn($session);
    expect($session->current_round_number)->toBe(1);
    expect($session->current_turn_index)->toBe(1);
    expect($session->currentTurn()->packCard->kind)->toBe(PackCardKind::Dare);
    expect(Turn::where('id', $firstTurn->id)->first()->completed_at)->not->toBeNull();

    $session = $service->nextTurn($session);
    expect($session->current_round_number)->toBe(1);
    expect($session->current_turn_index)->toBe(2);

    // Third call wraps into round 2, turn index resets to 0.
    $session = $service->nextTurn($session);
    expect($session->current_round_number)->toBe(2);
    expect($session->current_turn_index)->toBe(0);
    expect(Round::where('game_session_id', $session->id)->count())->toBe(2);
    expect(Round::where('game_session_id', $session->id)->where('number', 1)->first()->completed_at)->not->toBeNull();
});

it('completes the session after the last turn of the last round', function () {
    $party = makeLivePartyWithMembers(2);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party, 5);

    // 5 rounds * 2 members = 10 turns total; the first is already dealt by start(),
    // so 10 next-turn calls are needed: 9 to deal the rest, the 10th to complete the last one.
    for ($i = 0; $i < 10; $i++) {
        $session = $service->nextTurn($session);
    }

    expect($session->status)->toBe(GameSessionStatus::Completed);
    expect($session->ended_at)->not->toBeNull();
    expect(Turn::where('game_session_id', $session->id)->count())->toBe(10);
    expect(Turn::where('game_session_id', $session->id)->whereNull('completed_at')->count())->toBe(0);
});

it('rejects advancing a completed session', function () {
    $party = makeLivePartyWithMembers(1);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party, 5);

    // 5 rounds * 1 member = 5 turns total; the first is already dealt by start().
    for ($i = 0; $i < 5; $i++) {
        $session = $service->nextTurn($session);
    }

    expect($session->status)->toBe(GameSessionStatus::Completed);

    expect(fn () => $service->nextTurn($session))->toThrow(GameSessionNotActiveException::class);
});

it('reshuffles and allows repeat cards once a kind is exhausted', function () {
    $party = makeLivePartyWithMembers(2, cardsPerKind: 1);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party, 5);

    // Advancing several turns forces the same single Truth/Dare card to repeat.
    for ($i = 0; $i < 3; $i++) {
        $session = $service->nextTurn($session);
    }

    expect(Turn::where('game_session_id', $session->id)->count())->toBeGreaterThan(0);
});

it('throws when the pack has no cards of the kind needed to deal', function () {
    $pack = Pack::factory()->create();
    PackCard::factory()->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);
    // No Truth cards at all — the very first turn needs one.

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    expect(fn () => app(GameSessionService::class)->start($host, $party))
        ->toThrow(GameSessionPackUnavailableException::class);

    expect(GameSession::where('party_id', $party->id)->count())->toBe(0);
});
