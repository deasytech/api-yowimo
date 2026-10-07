<?php

use App\Enums\WalletTransactionType;
use App\Listeners\GrantGameCompletionReward;
use App\Models\PartyMember;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;
use Tests\Support\MakesLiveGameSessionParties;

uses(MakesLiveGameSessionParties::class);

function makeLivePartyForGameCompletionReward(int $memberCount): array
{
    return test()->makeLiveGameSessionParty($memberCount);
}

it('pushes the game-completion reward listener onto the queue when GameCompleted fires', function () {
    [$host, $party] = makeLivePartyForGameCompletionReward(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Queue::fake();

    $service->nextTurn($session);
    finishGameVotingWindow($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === GrantGameCompletionReward::class);
});

it('credits 25 tokens to every player who took a turn when the game completes', function () {
    [$host, $party, $userIds] = makeLivePartyForGameCompletionReward(2);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    // Drive every turn in the single round, then close the final voting window, to trigger GameCompleted.
    for ($turn = 0; $turn < count($session->turn_order); $turn++) {
        $session = $service->nextTurn($session->fresh());
    }
    finishGameVotingWindow($session);

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
    [$host, $party] = makeLivePartyForGameCompletionReward(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    $latecomer = PartyMember::factory()->create(['party_id' => $party->id]);

    for ($turn = 0; $turn < count($session->turn_order); $turn++) {
        $session = $service->nextTurn($session->fresh());
    }
    finishGameVotingWindow($session);

    expect($latecomer->user->wallet()->first())->toBeNull();
});
