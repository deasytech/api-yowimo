<?php

use App\Enums\WalletTransactionType;
use App\Exceptions\Api\InvalidReferralCodeException;
use App\Exceptions\Api\ReferralAlreadyClaimedException;
use App\Exceptions\Api\SelfReferralException;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Referrals\ReferralService;
use App\Services\Wallet\WalletService;

it('claims a valid referral code, setting referred_by_user_id', function () {
    $referrer = User::factory()->create();
    $user = User::factory()->create();

    app(ReferralService::class)->claim($user, $referrer->referral_code);

    expect($user->fresh()->referred_by_user_id)->toBe($referrer->id);
});

it('claims a code regardless of the case it was typed in', function () {
    $referrer = User::factory()->create(['referral_code' => 'ABCD1234']);
    $user = User::factory()->create();

    app(ReferralService::class)->claim($user, 'abcd1234');

    expect($user->fresh()->referred_by_user_id)->toBe($referrer->id);
});

it('rejects a code that does not resolve to any user', function () {
    $user = User::factory()->create();

    expect(fn () => app(ReferralService::class)->claim($user, 'NOSUCHCODE'))
        ->toThrow(InvalidReferralCodeException::class);

    expect($user->fresh()->referred_by_user_id)->toBeNull();
});

it('rejects a second claim once referred_by_user_id is already set, even for a different valid code', function () {
    $firstReferrer = User::factory()->create();
    $secondReferrer = User::factory()->create();
    $user = User::factory()->create(['referred_by_user_id' => $firstReferrer->id]);

    expect(fn () => app(ReferralService::class)->claim($user, $secondReferrer->referral_code))
        ->toThrow(ReferralAlreadyClaimedException::class);

    expect($user->fresh()->referred_by_user_id)->toBe($firstReferrer->id);
});

it('rejects claiming your own referral code', function () {
    $user = User::factory()->create();

    expect(fn () => app(ReferralService::class)->claim($user, $user->referral_code))
        ->toThrow(SelfReferralException::class);

    expect($user->fresh()->referred_by_user_id)->toBeNull();
});

it('reports a live summary of the referral code, referred count, tokens earned and reward amount', function () {
    config(['services.referrals.reward_amount' => 15]);

    $user = User::factory()->create();
    User::factory()->count(3)->create(['referred_by_user_id' => $user->id]);

    app(WalletService::class)->credit($user, 15, WalletTransactionType::Referral, description: 'test');
    app(WalletService::class)->credit($user, 15, WalletTransactionType::Referral, description: 'test');
    app(WalletService::class)->credit($user, 50, WalletTransactionType::Reward, description: 'unrelated');

    $summary = app(ReferralService::class)->summary($user);

    expect($summary)->toBe([
        'referral_code' => $user->referral_code,
        'referred_count' => 3,
        'tokens_earned' => 30,
        'reward_amount' => 15,
    ]);
});

it('pays out the configured reward to both parties exactly once per referred user', function () {
    config(['services.referrals.reward_amount' => 10]);

    $referrer = User::factory()->create();
    $referred = User::factory()->create(['referred_by_user_id' => $referrer->id]);

    app(ReferralService::class)->payoutFor($referred);
    app(ReferralService::class)->payoutFor($referred);

    expect($referrer->wallet->fresh()->balance)->toBe(10);
    expect($referred->wallet->fresh()->balance)->toBe(10);
    expect(WalletTransaction::where('wallet_id', $referrer->wallet->id)->where('type', WalletTransactionType::Referral)->count())->toBe(1);
    expect(WalletTransaction::where('wallet_id', $referred->wallet->id)->where('type', WalletTransactionType::Referral)->count())->toBe(1);
});

it('is a no-op payout for a user who was never referred', function () {
    $user = User::factory()->create();

    app(ReferralService::class)->payoutFor($user);

    expect($user->wallet()->first())->toBeNull();
});
