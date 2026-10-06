<?php

use App\Enums\AdRewardSessionStatus;
use App\Enums\WalletTransactionType;
use App\Models\AdRewardSession;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Ads\AdRewardService;
use Tests\Support\CreatesAdRewardSessions;
use Tests\Support\FakesAdMob;

uses(FakesAdMob::class, CreatesAdRewardSessions::class);

beforeEach(function () {
    $this->fakeAdMobSsvKeys();
});

function admobSsvEndpoint(string $signedQuery): string
{
    return "/api/v1/webhooks/admob-ssv?{$signedQuery}";
}

it('credits the wallet when a validly signed SSV callback arrives', function () {
    $user = User::factory()->create();
    $minted = app(AdRewardService::class)->mintSession($user);

    $query = $this->signedAdMobQuery([
        'ad_network' => '5450213213286189855',
        'ad_unit' => 'ca-app-pub-test/1234',
        'custom_data' => $minted['plaintext_token'],
        'reward_amount' => '1',
        'reward_item' => 'coins',
        'timestamp' => (string) now()->timestamp,
        'transaction_id' => 'txn-happy-path',
    ]);

    $this->getJson(admobSsvEndpoint($query))->assertStatus(200);

    expect($minted['model']->fresh()->status)->toBe(AdRewardSessionStatus::Credited);
    expect($user->wallet->fresh()->balance)->toBe(1);
});

it('rejects a tampered callback with 400 and credits nothing', function () {
    $user = User::factory()->create();
    $minted = app(AdRewardService::class)->mintSession($user);

    $query = $this->signedAdMobQuery(['custom_data' => $minted['plaintext_token'], 'transaction_id' => 'txn-1']);
    $tampered = str_replace('transaction_id=txn-1', 'transaction_id=txn-EVIL', $query);

    $this->getJson(admobSsvEndpoint($tampered))
        ->assertStatus(400)
        ->assertJson(['success' => false, 'message' => 'Invalid AdMob SSV callback signature.']);

    expect($minted['model']->fresh()->status)->toBe(AdRewardSessionStatus::Pending);
    expect($user->wallet()->exists())->toBeFalse();
});

it('rejects with 400 a callback with a duplicate custom_data appended after key_id, crediting neither session', function () {
    // Attack: capture one legitimately-signed callback for $victim's session,
    // then append a second custom_data pointing at $attacker's own session.
    // The signed prefix (and therefore the signature) is untouched, so a
    // naive implementation would still consider the signature valid while
    // resolving $request->query('custom_data') to the appended value —
    // crediting the attacker's session off a signature that never actually
    // covered their token.
    $victim = User::factory()->create();
    $attacker = User::factory()->create();
    $victimSession = app(AdRewardService::class)->mintSession($victim);
    $attackerSession = app(AdRewardService::class)->mintSession($attacker);

    $query = $this->signedAdMobQuery(['custom_data' => $victimSession['plaintext_token'], 'transaction_id' => 'txn-attack']);
    $forged = $query.'&custom_data='.$attackerSession['plaintext_token'];

    $this->getJson(admobSsvEndpoint($forged))
        ->assertStatus(400)
        ->assertJson(['success' => false, 'message' => 'Invalid AdMob SSV callback signature.']);

    expect($victimSession['model']->fresh()->status)->toBe(AdRewardSessionStatus::Pending);
    expect($attackerSession['model']->fresh()->status)->toBe(AdRewardSessionStatus::Pending);
    expect(WalletTransaction::where('type', WalletTransactionType::Reward)->exists())->toBeFalse();
});

it('returns 200 but credits only once when a valid callback is replayed', function () {
    $user = User::factory()->create();
    $minted = app(AdRewardService::class)->mintSession($user);
    $query = $this->signedAdMobQuery(['custom_data' => $minted['plaintext_token'], 'transaction_id' => 'txn-replay']);

    $this->getJson(admobSsvEndpoint($query))->assertStatus(200);
    $this->getJson(admobSsvEndpoint($query))->assertStatus(200);

    expect($user->wallet->fresh()->balance)->toBe(1);
    expect(WalletTransaction::where('type', WalletTransactionType::Reward)->count())->toBe(1);
});

it('returns 200 without crediting an expired session', function () {
    $user = User::factory()->create();
    ['session' => $session, 'plaintext' => $plaintext] = $this->expiredAdRewardSession($user);

    $query = $this->signedAdMobQuery(['custom_data' => $plaintext, 'transaction_id' => 'txn-expired']);

    $this->getJson(admobSsvEndpoint($query))->assertStatus(200);

    expect($session->fresh()->status)->toBe(AdRewardSessionStatus::Expired);
    expect($user->wallet()->exists())->toBeFalse();
});

it('returns 200 for a validly signed callback whose custom_data matches no known session', function () {
    $query = $this->signedAdMobQuery(['custom_data' => 'no-such-token', 'transaction_id' => 'txn-unknown']);

    $this->getJson(admobSsvEndpoint($query))->assertStatus(200);

    expect(WalletTransaction::where('type', WalletTransactionType::Reward)->exists())->toBeFalse();
});

it('returns 200 without crediting when rewarded ads are disabled mid-flight', function () {
    $user = User::factory()->create();
    $minted = app(AdRewardService::class)->mintSession($user);

    config(['services.admob.rewarded_ads_enabled' => false]);
    $query = $this->signedAdMobQuery(['custom_data' => $minted['plaintext_token'], 'transaction_id' => 'txn-disabled']);

    $this->getJson(admobSsvEndpoint($query))->assertStatus(200);

    expect($minted['model']->fresh()->status)->toBe(AdRewardSessionStatus::Expired);
    expect($user->wallet()->exists())->toBeFalse();
});

it('stops crediting once the daily cap is reached, resolving the session to expired', function () {
    $user = User::factory()->create();
    ['session' => $session, 'plaintext' => $plaintext] = $this->pendingAdRewardSessionOverDailyCap($user);

    $query = $this->signedAdMobQuery(['custom_data' => $plaintext, 'transaction_id' => 'txn-over-cap']);

    $this->getJson(admobSsvEndpoint($query))->assertStatus(200);

    expect($session->fresh()->status)->toBe(AdRewardSessionStatus::Expired);
    expect(AdRewardSession::where('user_id', $user->id)->where('status', AdRewardSessionStatus::Credited)->count())->toBe(15);
});
