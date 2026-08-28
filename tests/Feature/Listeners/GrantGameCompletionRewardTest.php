<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Enums\WalletTransactionType;
use App\Listeners\GrantGameCompletionReward;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

function makeLivePartyForGameCompletionReward(int $memberCount): array
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

it('pushes the game-completion reward listener onto the queue when GameCompleted fires', function () {
    [$host, $party] = makeLivePartyForGameCompletionReward(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Queue::fake();

    $service->nextTurn($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === GrantGameCompletionReward::class);
});

it('credits 25 tokens to every player who took a turn when the game completes', function () {
    [$host, $party, $userIds] = makeLivePartyForGameCompletionReward(2);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    // Drive every turn in the single round to trigger GameCompleted.
    foreach ($session->turn_order as $ignored) {
        $session = $service->nextTurn($session->fresh());
    }

    foreach ($userIds as $userId) {
        $user = User::find($userId);
        expect($user->wallet->balance)->toBe(25);

        $transaction = WalletTransaction::where('wallet_id', $user->wallet->id)
            ->where('type', WalletTransactionType::Reward)
            ->first();

        expect($transaction)->not->toBeNull();
        expect($transaction->amount)->toBe(25);
    }
});

it('does not reward a party member who joined after the game started and never took a turn', function () {
    [$host, $party, $userIds] = makeLivePartyForGameCompletionReward(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    $latecomer = PartyMember::factory()->create(['party_id' => $party->id]);

    foreach ($session->turn_order as $ignored) {
        $session = $service->nextTurn($session->fresh());
    }

    expect($latecomer->user->wallet()->first())->toBeNull();
});
