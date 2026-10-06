<?php

use App\Enums\AdRewardSessionStatus;
use App\Enums\WalletTransactionType;
use App\Exceptions\Api\AdRewardDailyCapReachedException;
use App\Exceptions\Api\AdRewardsDisabledException;
use App\Models\AdRewardSession;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Ads\AdRewardService;
use Tests\Support\CreatesAdRewardSessions;

uses(CreatesAdRewardSessions::class);

function mintedToken(User $user): array
{
    return app(AdRewardService::class)->mintSession($user);
}

it('mints a session with a plaintext token that hashes to the stored token_hash', function () {
    $user = User::factory()->create();

    $minted = mintedToken($user);

    expect($minted['plaintext_token'])->toBeString()->not->toBeEmpty();
    expect($minted['model']->token_hash)->toBe(AdRewardSession::hashToken($minted['plaintext_token']));
    expect($minted['model']->status)->toBe(AdRewardSessionStatus::Pending);
    expect($minted['model']->expires_at->isFuture())->toBeTrue();
});

it('refuses to mint once the daily cap is already reached', function () {
    $user = User::factory()->create();
    AdRewardSession::factory()->credited()->count(15)->create(['user_id' => $user->id]);

    expect(fn () => mintedToken($user))->toThrow(AdRewardDailyCapReachedException::class);
});

it('reports daily progress with the correct remaining count and reset time', function () {
    $user = User::factory()->create();
    AdRewardSession::factory()->credited()->count(4)->create(['user_id' => $user->id]);
    // A pending (not yet credited) session shouldn't count toward progress.
    AdRewardSession::factory()->create(['user_id' => $user->id]);

    $progress = app(AdRewardService::class)->dailyProgress($user);

    expect($progress['watched_today'])->toBe(4);
    expect($progress['daily_cap'])->toBe((int) config('services.admob.daily_token_limit'));
    expect($progress['remaining'])->toBe(11);
    expect($progress['next_reset_at']->isAfter(now()))->toBeTrue();
    expect($progress['enabled'])->toBeTrue();
    expect($progress['tokens_per_ad'])->toBe((int) config('services.admob.tokens_per_completed_ad'));
    expect($progress['can_earn'])->toBeTrue();
    expect($progress['server_date'])->toBe(today()->toDateString());
});

it('reports can_earn false once the cap is reached, and false (not just remaining=0) when disabled', function () {
    $cappedUser = User::factory()->create();
    AdRewardSession::factory()->credited()->count(15)->create(['user_id' => $cappedUser->id]);
    expect(app(AdRewardService::class)->dailyProgress($cappedUser)['can_earn'])->toBeFalse();

    config(['services.admob.rewarded_ads_enabled' => false]);
    $freshUser = User::factory()->create();
    $progress = app(AdRewardService::class)->dailyProgress($freshUser);

    expect($progress['remaining'])->toBeGreaterThan(0);
    expect($progress['enabled'])->toBeFalse();
    expect($progress['can_earn'])->toBeFalse();
});

it('refuses to mint while rewarded ads are disabled, even with cap remaining', function () {
    config(['services.admob.rewarded_ads_enabled' => false]);
    $user = User::factory()->create();

    expect(fn () => mintedToken($user))->toThrow(AdRewardsDisabledException::class);
});

it('respects a configured daily limit and reward amount other than the defaults', function () {
    config(['services.admob.daily_token_limit' => 3, 'services.admob.tokens_per_completed_ad' => 5]);
    $user = User::factory()->create();

    $minted = mintedToken($user);
    expect($minted['model']->reward_amount)->toBe(5);

    $progress = app(AdRewardService::class)->dailyProgress($user);
    expect($progress['daily_cap'])->toBe(3);
    expect($progress['remaining'])->toBe(3);

    AdRewardSession::factory()->credited()->count(3)->create(['user_id' => $user->id]);
    expect(fn () => mintedToken($user))->toThrow(AdRewardDailyCapReachedException::class);
});

it('credits the wallet and marks the session credited on a verified callback', function () {
    $user = User::factory()->create();
    $minted = mintedToken($user);

    app(AdRewardService::class)->verifyAndCredit([
        'custom_data' => $minted['plaintext_token'],
        'transaction_id' => 'txn-1',
        'ad_network' => '5450213213286189855',
        'ad_unit' => 'ca-app-pub-test/1234',
        'reward_item' => 'coins',
        'reward_amount' => '1',
        'timestamp' => (string) now()->timestamp,
    ]);

    $session = $minted['model']->fresh();
    expect($session->status)->toBe(AdRewardSessionStatus::Credited);
    expect($session->ad_network_transaction_id)->toBe('txn-1');
    expect($session->wallet_transaction_id)->not->toBeNull();
    expect($user->wallet->fresh()->balance)->toBe(1);

    $transaction = WalletTransaction::find($session->wallet_transaction_id);
    expect($transaction->type)->toBe(WalletTransactionType::Reward);
    expect($transaction->amount)->toBe(1);
    expect($transaction->reference_type)->toBe($session->getMorphClass());
});

it('is a no-op for an unknown token', function () {
    $user = User::factory()->create();
    Wallet::factory()->create(['user_id' => $user->id, 'balance' => 0]);

    app(AdRewardService::class)->verifyAndCredit(['custom_data' => 'not-a-real-token']);

    expect($user->wallet->fresh()->balance)->toBe(0);
});

it('does not credit twice when the same valid callback is replayed', function () {
    $user = User::factory()->create();
    $minted = mintedToken($user);
    $params = ['custom_data' => $minted['plaintext_token'], 'transaction_id' => 'txn-replay'];

    app(AdRewardService::class)->verifyAndCredit($params);
    app(AdRewardService::class)->verifyAndCredit($params);

    expect($user->wallet->fresh()->balance)->toBe(1);
    expect(WalletTransaction::where('type', WalletTransactionType::Reward)->count())->toBe(1);
});

it('marks an expired session expired instead of crediting it', function () {
    $user = User::factory()->create();
    ['session' => $session, 'plaintext' => $plaintext] = $this->expiredAdRewardSession($user);

    app(AdRewardService::class)->verifyAndCredit(['custom_data' => $plaintext]);

    expect($session->fresh()->status)->toBe(AdRewardSessionStatus::Expired);
    expect($user->wallet()->exists())->toBeFalse();
});

it('rejects crediting a pending session if rewarded ads were disabled after it was minted', function () {
    $user = User::factory()->create();
    $minted = mintedToken($user);

    config(['services.admob.rewarded_ads_enabled' => false]);
    app(AdRewardService::class)->verifyAndCredit(['custom_data' => $minted['plaintext_token']]);

    expect($minted['model']->fresh()->status)->toBe(AdRewardSessionStatus::Expired);
    expect($minted['model']->fresh()->metadata)->toBe(['reason' => 'rewarded_ads_disabled']);
    expect($user->wallet()->exists())->toBeFalse();
});

it('rejects crediting past the daily cap even for a structurally valid pending session', function () {
    $user = User::factory()->create();
    ['session' => $session, 'plaintext' => $plaintext] = $this->pendingAdRewardSessionOverDailyCap($user);

    app(AdRewardService::class)->verifyAndCredit(['custom_data' => $plaintext]);

    expect($session->fresh()->status)->toBe(AdRewardSessionStatus::Expired);
    expect($session->fresh()->metadata)->toBe(['reason' => 'daily_cap_reached']);
    expect(AdRewardSession::where('user_id', $user->id)->where('status', AdRewardSessionStatus::Credited)->count())->toBe(15);
});
