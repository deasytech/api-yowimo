<?php

use App\Models\User;
use Tests\Support\FakesClerk;

const REFERRALS_CLAIM_ENDPOINT = '/api/v1/referrals/claim';
const REFERRALS_SUMMARY_ENDPOINT = '/api/v1/referrals/summary';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects claiming with no bearer token', function () {
    $this->postJson(REFERRALS_CLAIM_ENDPOINT, ['code' => 'ANYCODE'])->assertStatus(401);
});

it('rejects claiming without a code', function () {
    authAs('user_referral_claim_missing_code');

    $this->postJson(REFERRALS_CLAIM_ENDPOINT, [])->assertStatus(422);
});

it('claims a valid referral code', function () {
    $referrer = User::factory()->create();
    $user = authAs('user_referral_claim_valid');

    $this->postJson(REFERRALS_CLAIM_ENDPOINT, ['code' => $referrer->referral_code])
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($user->fresh()->referred_by_user_id)->toBe($referrer->id);
});

it('returns 422 for a code that does not resolve to any user', function () {
    authAs('user_referral_claim_invalid');

    $this->postJson(REFERRALS_CLAIM_ENDPOINT, ['code' => 'NOSUCHCODE'])
        ->assertStatus(422);
});

it('returns 409 when the caller already claimed a code', function () {
    $firstReferrer = User::factory()->create();
    $secondReferrer = User::factory()->create();
    $user = authAs('user_referral_claim_twice');
    $user->update(['referred_by_user_id' => $firstReferrer->id]);

    $this->postJson(REFERRALS_CLAIM_ENDPOINT, ['code' => $secondReferrer->referral_code])
        ->assertStatus(409);
});

it('returns 422 for a self-referral attempt', function () {
    $user = authAs('user_referral_self');

    $this->postJson(REFERRALS_CLAIM_ENDPOINT, ['code' => $user->referral_code])
        ->assertStatus(422);
});

it('returns the viewer\'s referral summary', function () {
    config(['services.referrals.reward_amount' => 15]);

    $user = authAs('user_referral_summary');
    User::factory()->count(2)->create(['referred_by_user_id' => $user->id]);

    $this->getJson(REFERRALS_SUMMARY_ENDPOINT)
        ->assertStatus(200)
        ->assertJsonPath('data.referral_code', $user->referral_code)
        ->assertJsonPath('data.referred_count', 2)
        ->assertJsonPath('data.tokens_earned', 0)
        ->assertJsonPath('data.reward_amount', 15);
});
