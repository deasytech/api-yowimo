<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Enums\VoteCategory;
use App\Enums\XpTransactionType;
use App\Listeners\GrantMvpBonus;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\Turn;
use App\Models\User;
use App\Models\XpTransaction;
use App\Services\Game\GameSessionService;
use App\Services\Game\VoteService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

function makeLivePartyForMvpBonus(int $memberCount): array
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);

    $members = collect([PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id])]);
    for ($i = 1; $i < $memberCount; $i++) {
        $members->push(PartyMember::factory()->create(['party_id' => $party->id]));
    }

    return [$host, $party->fresh(), $members->pluck('user_id')];
}

it('pushes the MVP-bonus listener onto the queue when GameCompleted fires', function () {
    [$host, $party] = makeLivePartyForMvpBonus(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Queue::fake();

    $service->nextTurn($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === GrantMvpBonus::class);
});

it('awards the MVP bonus to every player tied for the highest XP when nobody voted', function () {
    [$host, $party, $userIds] = makeLivePartyForMvpBonus(2);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    foreach ($session->turn_order as $ignored) {
        $session = $service->nextTurn($session->fresh());
    }

    foreach ($userIds as $userId) {
        $user = User::find($userId);
        expect($user->xp)->toBe(150); // 50 (challenge completed) + 100 (MVP, tied)
    }
});

it('awards the MVP bonus only to the sole top scorer when votes differentiate players', function () {
    [$host, $party, $userIds] = makeLivePartyForMvpBonus(3);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    $firstTurn = $session->currentTurn();
    $firstTurnOwnerId = $firstTurn->user_id;

    $session = $service->nextTurn($session);

    $voterId = $userIds->first(fn ($id) => $id !== $firstTurnOwnerId);
    app(VoteService::class)->cast(User::find($voterId), Turn::find($firstTurn->id), VoteCategory::Winner);

    $session = $service->nextTurn($session->fresh());
    $service->nextTurn($session->fresh());

    $topScorer = User::find($firstTurnOwnerId);
    expect($topScorer->xp)->toBe(175); // 50 (own challenge) + 25 (winner vote) + 100 (MVP)

    foreach ($userIds as $userId) {
        if ($userId === $firstTurnOwnerId) {
            continue;
        }

        expect(User::find($userId)->xp)->toBe(50);
    }

    $mvpTransaction = XpTransaction::where('user_id', $firstTurnOwnerId)
        ->where('type', XpTransactionType::MvpBonus)
        ->first();

    expect($mvpTransaction)->not->toBeNull();
    expect($mvpTransaction->amount)->toBe(100);
});
