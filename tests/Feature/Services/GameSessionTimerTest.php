<?php

use App\Enums\GameSessionStatus;
use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Jobs\SkipAfkTurn;
use App\Models\GameSession;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\Turn;
use App\Models\User;
use App\Services\Game\GameSessionService;
use Illuminate\Support\Facades\Queue;

function makeTimerTestParty(int $memberCount = 2, int $cardsPerKind = 20): Party
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

it('dispatches a delayed AFK-skip job when a turn is dealt', function () {
    Queue::fake();

    $party = makeTimerTestParty(2);
    $session = app(GameSessionService::class)->start($party->host, $party);
    $turn = $session->currentTurn();

    Queue::assertPushed(SkipAfkTurn::class, fn ($job) => $job->turnId === $turn->id && $job->delay !== null);
});

function expireCurrentTurn(GameSession $session): Turn
{
    $turn = $session->currentTurn();
    $turn->update(['started_at' => now()->subSeconds(GameSessionService::TURN_TIMEOUT_SECONDS + 1)]);

    return $turn;
}

it('does not skip a turn before its timer has actually elapsed', function () {
    Queue::fake();

    $party = makeTimerTestParty(2);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party);
    $firstTurn = $session->currentTurn();

    $result = $service->skipAfkTurn($firstTurn->id);

    expect($result)->toBeNull();
    expect($firstTurn->fresh()->is_afk)->toBeFalse();
});

it('AFK-skips the current turn and advances when its timer expires', function () {
    Queue::fake();

    $party = makeTimerTestParty(2);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party);
    $firstTurn = expireCurrentTurn($session);

    $updated = $service->skipAfkTurn($firstTurn->id);

    expect($updated)->not->toBeNull();
    expect($updated->current_turn_index)->toBe(1);

    $firstTurn->refresh();
    expect($firstTurn->is_afk)->toBeTrue();
    expect($firstTurn->completed_at)->not->toBeNull();
});

it('does not reprocess a turn the host already completed normally', function () {
    Queue::fake();

    $party = makeTimerTestParty(2);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party);
    $firstTurn = expireCurrentTurn($session);

    $session = $service->nextTurn($session);
    expect($session->current_turn_index)->toBe(1);

    $result = $service->skipAfkTurn($firstTurn->id);

    expect($result)->toBeNull();
    expect($session->fresh()->current_turn_index)->toBe(1);
    expect($firstTurn->fresh()->is_afk)->toBeFalse();
});

it('completes the round and game via an AFK skip on the last turn, same as a normal advance', function () {
    Queue::fake();

    $party = makeTimerTestParty(1);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party, 5);

    for ($i = 0; $i < 5; $i++) {
        $turn = expireCurrentTurn($session);
        $session = $service->skipAfkTurn($turn->id);
    }

    expect($session->status)->toBe(GameSessionStatus::Completed);
    expect($session->ended_at)->not->toBeNull();
    expect(Turn::where('game_session_id', $session->id)->where('is_afk', true)->count())->toBe(5);
});

it('sweeps and AFK-skips a turn whose delayed job never ran (crash recovery)', function () {
    Queue::fake();

    $party = makeTimerTestParty(2);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party);
    $turn = $session->currentTurn();

    $turn->update(['started_at' => now()->subSeconds(GameSessionService::TURN_TIMEOUT_SECONDS + 5)]);

    $skipped = $service->sweepExpiredTurns();

    expect($skipped)->toBe(1);
    expect($turn->fresh()->is_afk)->toBeTrue();
    expect($session->fresh()->current_turn_index)->toBe(1);
});

it('does not sweep a turn that is still within its timer window', function () {
    Queue::fake();

    $party = makeTimerTestParty(2);
    $service = app(GameSessionService::class);
    $session = $service->start($party->host, $party);

    expect($service->sweepExpiredTurns())->toBe(0);
    expect($session->fresh()->current_turn_index)->toBe(0);
});
