<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Enums\XpTransactionType;
use App\Listeners\GrantChallengeCompletionXp;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

function makeLivePartyForChallengeXp(int $memberCount): array
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);

    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    for ($i = 1; $i < $memberCount; $i++) {
        PartyMember::factory()->create(['party_id' => $party->id]);
    }

    return [$host, $party->fresh()];
}

it('pushes the challenge-completion XP listener onto the queue when TurnCompleted fires', function () {
    [$host, $party] = makeLivePartyForChallengeXp(2);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Queue::fake();

    $service->nextTurn($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === GrantChallengeCompletionXp::class);
});

it('credits 50 XP to the turn player when their turn completes normally', function () {
    // Two members so the first `nextTurn()` only completes turn 0 (deals turn
    // 1) without also completing the round/game — isolates the Challenge
    // Completed XP from the MVP bonus, which is covered separately.
    [$host, $party] = makeLivePartyForChallengeXp(2);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);
    $turnOwnerId = $session->currentTurn()->user_id;

    $service->nextTurn($session);

    $owner = User::find($turnOwnerId);
    expect($owner->xp)->toBe(50);

    $transaction = XpTransaction::where('user_id', $turnOwnerId)
        ->where('type', XpTransactionType::ChallengeCompleted)
        ->first();

    expect($transaction)->not->toBeNull();
    expect($transaction->amount)->toBe(50);
});

it('does not credit XP for a turn that was AFK-skipped', function () {
    [$host, $party] = makeLivePartyForChallengeXp(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);
    $turn = $session->currentTurn();
    $turnOwnerId = $turn->user_id;

    $turn->update(['started_at' => now()->subSeconds(GameSessionService::TURN_TIMEOUT_SECONDS + 1)]);
    $service->skipAfkTurn($turn->id);

    $owner = User::find($turnOwnerId);
    expect($owner->xp)->toBe(0);
    expect(XpTransaction::where('user_id', $turnOwnerId)->exists())->toBeFalse();
});
