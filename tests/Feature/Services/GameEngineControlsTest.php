<?php

use App\Enums\GameSessionStatus;
use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Enums\VoteCategory;
use App\Enums\XpTransactionType;
use App\Events\GameCompleted;
use App\Events\GameEnded;
use App\Events\GamePaused;
use App\Events\GameResumed;
use App\Events\GameStarted;
use App\Events\GameVotingStarted;
use App\Events\ReactionSent;
use App\Events\TurnStarted;
use App\Exceptions\Api\GameSessionAlreadyActiveException;
use App\Exceptions\Api\GameSessionNotActiveException;
use App\Exceptions\Api\TurnNotActiveException;
use App\Exceptions\Api\VotingNotAllowedException;
use App\Models\GameSession;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\Turn;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\Game\GameResultsService;
use App\Services\Game\GameSessionService;
use App\Services\Game\ReactionService;
use App\Services\Game\VoteService;
use App\Services\Parties\PartyMembershipService;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

/**
 * A live party with `$memberCount` active members (the host first). Queue is
 * faked so AFK/voting timer jobs are only recorded, never run.
 *
 * @return array{0: Party, 1: GameSessionService}
 */
function makeEngineTestParty(int $memberCount = 3): array
{
    Queue::fake();

    $pack = Pack::factory()->create();
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'pack_id' => $pack->id,
        'status' => PartyStatus::Live,
        'players_count' => $memberCount,
        'max_players' => 10,
    ]);

    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    for ($i = 1; $i < $memberCount; $i++) {
        PartyMember::factory()->create(['party_id' => $party->id]);
    }

    return [$party->fresh(), app(GameSessionService::class)];
}

function currentTurnOf(GameSession $session): Turn
{
    return $session->fresh()->currentTurn();
}

// ---------- Start ----------

it('uses the host-chosen turn timer, defaulting to 30 seconds', function () {
    [$party, $service] = makeEngineTestParty(2);

    $session = $service->start($party->host, $party, 5, 60);
    $turn = currentTurnOf($session);

    expect($session->turn_seconds)->toBe(60);
    expect((int) $turn->started_at->diffInSeconds($turn->expires_at))->toBe(60);

    [$party2, $service] = makeEngineTestParty(2);
    expect($service->start($party2->host, $party2)->turn_seconds)->toBe(30);
});

it('announces a new game on the party channel and sends the card with turn.started', function () {
    [$party, $service] = makeEngineTestParty(2);
    Event::fake([GameStarted::class, TurnStarted::class]);

    $session = $service->start($party->host, $party, 5);
    $turn = currentTurnOf($session);

    Event::assertDispatched(GameStarted::class, fn ($event) => $event->gameSessionId === $session->id
        && in_array(new PresenceChannel("party.{$party->id}"), $event->broadcastOn(), false));
    Event::assertDispatched(TurnStarted::class, fn ($event) => $event->turnId === $turn->id
        && $event->card['id'] === $turn->pack_card_id
        && $event->expiresAt === $turn->expires_at->toIso8601String());
});

it('refuses to start a second game while one is still in its voting window', function () {
    [$party, $service] = makeEngineTestParty(1);
    $session = $service->start($party->host, $party, 5);
    GameSession::whereKey($session->id)->update(['status' => GameSessionStatus::Voting]);

    expect(fn () => $service->start($party->host, $party, 5))
        ->toThrow(GameSessionAlreadyActiveException::class);
});

// ---------- Player turn actions ----------

it('lets a turn be completed, granting challenge XP and dealing the next turn', function () {
    [$party, $service] = makeEngineTestParty(2);
    $session = $service->start($party->host, $party, 5);
    $turn = currentTurnOf($session);

    $session = $service->completeTurn($session, $turn);

    expect($turn->fresh()->completed_at)->not->toBeNull();
    expect($turn->fresh()->is_skipped)->toBeFalse();
    expect($session->current_turn_index)->toBe(1);
    expect(XpTransaction::where('user_id', $turn->user_id)->where('type', XpTransactionType::ChallengeCompleted)->exists())->toBeTrue();
});

it('lets a turn be skipped: no challenge XP and no votes on it', function () {
    [$party, $service] = makeEngineTestParty(2);
    $session = $service->start($party->host, $party, 5);
    $turn = currentTurnOf($session);

    $service->completeTurn($session, $turn, skipped: true);

    expect($turn->fresh()->is_skipped)->toBeTrue();
    expect(XpTransaction::where('user_id', $turn->user_id)->exists())->toBeFalse();

    $voter = User::find(collect($session->turn_order)->first(fn ($id) => $id !== $turn->user_id));
    expect(fn () => app(VoteService::class)->cast($voter, $turn->fresh(), VoteCategory::Funny))
        ->toThrow(VotingNotAllowedException::class);
});

it('rejects acting on a turn that is no longer the open, current one', function () {
    [$party, $service] = makeEngineTestParty(2);
    $session = $service->start($party->host, $party, 5);
    $turn = currentTurnOf($session);

    $service->completeTurn($session, $turn);

    expect(fn () => $service->completeTurn($session, $turn->fresh()))->toThrow(TurnNotActiveException::class);
});

// ---------- Final voting window ----------

it('opens a voting window after the last turn in which the final turn can still be voted on', function () {
    [$party, $service] = makeEngineTestParty(2);
    Event::fake([GameVotingStarted::class, GameCompleted::class]);

    $session = $service->start($party->host, $party, 5);
    for ($i = 0; $i < 10; $i++) {
        $session = $service->nextTurn($session);
    }
    $finalTurn = currentTurnOf($session);

    expect($session->status)->toBe(GameSessionStatus::Voting);
    expect($session->voting_ends_at)->not->toBeNull();
    Event::assertDispatched(GameVotingStarted::class);
    Event::assertNotDispatched(GameCompleted::class);

    $voter = User::find(collect($session->turn_order)->first(fn ($id) => $id !== $finalTurn->user_id));
    app(VoteService::class)->cast($voter, $finalTurn, VoteCategory::Winner);

    expect($service->finishVoting($session->id))->toBeNull();

    $this->travel(GameSessionService::VOTING_WINDOW_SECONDS + 1)->seconds();

    expect($service->sweepExpiredVotingWindows())->toBe(1);
    expect($session->fresh()->status)->toBe(GameSessionStatus::Completed);
    Event::assertDispatched(GameCompleted::class);
});

// ---------- Early end ----------

it('ends an unfinished game without completion rewards when the host ends the party', function () {
    [$party, $service] = makeEngineTestParty(2);
    Event::fake([GameEnded::class, GameCompleted::class]);

    $session = $service->start($party->host, $party, 5);
    $openTurn = currentTurnOf($session);

    app(PartyMembershipService::class)->end($party);

    $session->refresh();
    expect($session->status)->toBe(GameSessionStatus::Ended);
    expect($session->ended_at)->not->toBeNull();
    expect($openTurn->fresh()->completed_at)->not->toBeNull();
    Event::assertDispatched(GameEnded::class, fn ($event) => $event->gameSessionId === $session->id);
    Event::assertNotDispatched(GameCompleted::class);

    $this->travel(GameSessionService::TURN_TIMEOUT_SECONDS + 1)->seconds();
    expect($service->skipAfkTurn($openTurn->id))->toBeNull();
});

// ---------- Players leaving and joining ----------

it('skips the current player immediately when they leave the party on their turn', function () {
    [$party, $service] = makeEngineTestParty(3);
    $session = $service->start($party->host, $party, 5);

    // The host can't leave, so advance to a non-host player's turn.
    while (currentTurnOf($session)->user_id === $party->host_id) {
        $session = $service->nextTurn($session);
    }
    $turn = currentTurnOf($session);

    app(PartyMembershipService::class)->leave(User::find($turn->user_id), $party);

    expect($turn->fresh()->is_skipped)->toBeTrue();
    expect(currentTurnOf($session)->id)->not->toBe($turn->id);
    expect(currentTurnOf($session)->completed_at)->toBeNull();
});

it('skips over players who left instead of dealing them a turn', function () {
    [$party, $service] = makeEngineTestParty(3);
    $session = $service->start($party->host, $party, 5);

    $leaverId = collect($session->turn_order)->last(fn ($id) => $id !== $party->host_id);
    app(PartyMembershipService::class)->leave(User::find($leaverId), $party);
    $lastTurnIdBeforeLeaving = Turn::where('game_session_id', $session->id)->max('id');

    // Two full rounds for the two remaining players.
    for ($i = 0; $i < 4; $i++) {
        $session = $service->nextTurn($session);
    }

    expect(Turn::where('game_session_id', $session->id)
        ->where('id', '>', $lastTurnIdBeforeLeaving)
        ->where('user_id', $leaverId)
        ->exists())->toBeFalse();
    expect($session->current_round_number)->toBeGreaterThan(1);
});

it('adds a player who joins mid-game to the end of the turn order, once', function () {
    [$party, $service] = makeEngineTestParty(2);
    $session = $service->start($party->host, $party, 5);
    $latecomer = User::factory()->create();

    app(PartyMembershipService::class)->join($latecomer, $party);

    $session->refresh();
    expect(collect($session->turn_order)->last())->toBe($latecomer->id);

    $session = $service->nextTurn($session);
    $session = $service->nextTurn($session);
    expect(currentTurnOf($session)->user_id)->toBe($latecomer->id);

    app(PartyMembershipService::class)->leave($latecomer, $party);
    app(PartyMembershipService::class)->join($latecomer, $party->fresh());
    expect(collect($session->fresh()->turn_order)->filter(fn ($id) => $id === $latecomer->id))->toHaveCount(1);
});

// ---------- Pause / resume ----------

it('freezes the turn timer while paused and restores the remaining time on resume', function () {
    [$party, $service] = makeEngineTestParty(2);
    Event::fake([GamePaused::class, GameResumed::class]);

    $session = $service->start($party->host, $party, 5, 30);
    $turn = currentTurnOf($session);

    $this->travel(10)->seconds();
    $session = $service->pause($session);

    expect($session->status)->toBe(GameSessionStatus::Paused);
    expect($session->paused_turn_remaining_seconds)->toBe(20);
    Event::assertDispatched(GamePaused::class, fn ($event) => $event->turnRemainingSeconds === 20);

    // Well past the original deadline, but the game is paused.
    $this->travel(60)->seconds();
    expect($service->skipAfkTurn($turn->id))->toBeNull();
    expect($service->sweepExpiredTurns())->toBe(0);
    expect(fn () => $service->nextTurn($session))->toThrow(GameSessionNotActiveException::class);

    $session = $service->resume($session);

    expect($session->status)->toBe(GameSessionStatus::Running);
    // Timestamps are stored to the second, so up to one sub-second is lost.
    expect(now()->diffInSeconds($turn->fresh()->expires_at))->toBeGreaterThan(19)->toBeLessThanOrEqual(20);
    Event::assertDispatched(GameResumed::class, fn ($event) => $event->turnId === $turn->id);
});

it('moves on to the next player on resume if the current player left while paused', function () {
    [$party, $service] = makeEngineTestParty(3);
    $session = $service->start($party->host, $party, 5);
    while (currentTurnOf($session)->user_id === $party->host_id) {
        $session = $service->nextTurn($session);
    }
    $turn = currentTurnOf($session);

    $service->pause($session);
    app(PartyMembershipService::class)->leave(User::find($turn->user_id), $party);

    expect($turn->fresh()->is_skipped)->toBeTrue();

    $session = $service->resume($session);

    expect(currentTurnOf($session)->id)->not->toBe($turn->id);
    expect(currentTurnOf($session)->completed_at)->toBeNull();
});

it('only pauses a running game and only resumes a paused one', function () {
    [$party, $service] = makeEngineTestParty(1);
    $session = $service->start($party->host, $party, 5);

    expect(fn () => $service->resume($session))->toThrow(GameSessionNotActiveException::class, 'This game is not paused.');

    $service->pause($session);

    expect(fn () => $service->pause($session))->toThrow(GameSessionNotActiveException::class, 'Only a running game can be paused.');
});

// ---------- Results and reactions ----------

it('reports standings with XP, votes, turn outcomes, and the MVP', function () {
    [$party, $service] = makeEngineTestParty(2);
    $session = $service->start($party->host, $party, 5);

    $first = currentTurnOf($session);
    $session = $service->completeTurn($session, $first);
    $second = currentTurnOf($session);
    $session = $service->completeTurn($session, $second, skipped: true);

    app(VoteService::class)->cast(User::find($second->user_id), $first->fresh(), VoteCategory::Funny);

    $standings = app(GameResultsService::class)->standings($session->fresh());
    $leader = $standings->first();

    expect($standings)->toHaveCount(2);
    expect($leader['user']->id)->toBe($first->user_id);
    expect($leader['xp'])->toBeGreaterThan(0);
    expect($leader['votes'])->toBe(['winner' => 0, 'funny' => 1, 'creativity' => 0]);
    expect($leader['turns'])->toBe(['completed' => 1, 'skipped' => 0, 'afk' => 0]);
    expect($standings->last()['turns'])->toBe(['completed' => 0, 'skipped' => 1, 'afk' => 0]);
    expect($leader['is_mvp'])->toBeFalse();
});

it('broadcasts reactions during a game and rejects them once it has finished', function () {
    [$party, $service] = makeEngineTestParty(1);
    Event::fake([ReactionSent::class]);

    $session = $service->start($party->host, $party, 5);

    app(ReactionService::class)->send($party->host, $session, '🔥');

    Event::assertDispatched(ReactionSent::class, fn ($event) => $event->emoji === '🔥' && $event->userId === $party->host_id);

    GameSession::whereKey($session->id)->update(['status' => GameSessionStatus::Completed]);

    expect(fn () => app(ReactionService::class)->send($party->host, $session->fresh(), '🔥'))
        ->toThrow(GameSessionNotActiveException::class);
});
