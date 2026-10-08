<?php

use App\Enums\WalletTransactionType;
use App\Listeners\GrantReferralReward;
use App\Models\GameSession;
use App\Models\Party;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;
use Tests\Support\MakesLiveGameSessionParties;

uses(MakesLiveGameSessionParties::class);

function playOneRoundSoloGame(User $host, Party $party): GameSession
{
    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    for ($turn = 0; $turn < count($session->turn_order); $turn++) {
        $session = $service->nextTurn($session->fresh());
    }

    finishGameVotingWindow($session);

    return $session->fresh();
}

it('pushes the referral-reward listener onto the queue when GameCompleted fires', function () {
    $referrer = User::factory()->create();
    $host = User::factory()->create(['referred_by_user_id' => $referrer->id]);
    [, $party] = test()->makeLiveGameSessionParty(1, host: $host);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Queue::fake();

    $service->nextTurn($session);
    finishGameVotingWindow($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === GrantReferralReward::class);
});

it("credits both the referrer and the referred user the configured reward on the referred user's first completed game", function () {
    config(['services.referrals.reward_amount' => 20]);

    $referrer = User::factory()->create();
    $host = User::factory()->create(['referred_by_user_id' => $referrer->id]);
    [, $party] = test()->makeLiveGameSessionParty(1, host: $host);

    playOneRoundSoloGame($host, $party);

    // The host (the referred user) also earns the unrelated 25-token
    // game-completion reward from GrantGameCompletionReward for the same
    // event, so the referral credit is asserted by transaction type/amount
    // rather than by the wallet's total balance.
    $referrerReferralTransaction = WalletTransaction::where('wallet_id', $referrer->wallet->id)->where('type', WalletTransactionType::Referral)->first();
    $hostReferralTransaction = WalletTransaction::where('wallet_id', $host->wallet->id)->where('type', WalletTransactionType::Referral)->first();

    expect($referrerReferralTransaction)->not->toBeNull();
    expect($referrerReferralTransaction->amount)->toBe(20);
    expect($hostReferralTransaction)->not->toBeNull();
    expect($hostReferralTransaction->amount)->toBe(20);
});

it('does not pay out again on a second completed game by the same referred user', function () {
    $referrer = User::factory()->create();
    $host = User::factory()->create(['referred_by_user_id' => $referrer->id]);
    [, $party] = test()->makeLiveGameSessionParty(1, host: $host);

    playOneRoundSoloGame($host, $party);
    playOneRoundSoloGame($host, $party);

    expect(WalletTransaction::where('wallet_id', $referrer->wallet->id)->where('type', WalletTransactionType::Referral)->count())->toBe(1);
});

it('does not pay out for a user who was never referred', function () {
    $host = User::factory()->create();
    [, $party] = test()->makeLiveGameSessionParty(1, host: $host);

    playOneRoundSoloGame($host, $party);

    // The host's wallet exists regardless, from the unrelated
    // game-completion reward — only the absence of a Referral transaction
    // proves no payout happened.
    expect(WalletTransaction::where('wallet_id', $host->wallet->id)->where('type', WalletTransactionType::Referral)->exists())->toBeFalse();
});

it('is a no-op when the referrer has been deleted', function () {
    $referrer = User::factory()->create();
    $host = User::factory()->create(['referred_by_user_id' => $referrer->id]);
    [, $party] = test()->makeLiveGameSessionParty(1, host: $host);

    // A soft delete, unlike forceDelete(), leaves referred_by_user_id intact
    // (the FK's nullOnDelete only fires on a real row deletion) — this is
    // what actually exercises payoutFor()'s "referrer not found" guard via
    // User::find(), which excludes trashed rows by default.
    $referrer->delete();

    playOneRoundSoloGame($host, $party);

    expect(WalletTransaction::where('wallet_id', $host->wallet->id)->where('type', WalletTransactionType::Referral)->exists())->toBeFalse();
});
