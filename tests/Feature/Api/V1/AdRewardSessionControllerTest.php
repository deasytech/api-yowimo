<?php

use App\Models\AdRewardSession;
use App\Models\User;
use Tests\Support\FakesClerk;

const AD_REWARD_SESSIONS_ENDPOINT = '/api/v1/ad-rewards/sessions';
const AD_REWARD_PROGRESS_ENDPOINT = '/api/v1/ad-rewards/progress';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects minting a session with no bearer token', function () {
    $this->postJson(AD_REWARD_SESSIONS_ENDPOINT)->assertStatus(401);
});

it('mints a session token without exposing the row id or status', function () {
    $token = $this->clerkToken(['sub' => 'user_ad_reward_mint']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(AD_REWARD_SESSIONS_ENDPOINT)
        ->assertStatus(201);

    $response->assertJsonStructure(['data' => ['token', 'expires_at']]);
    expect(array_keys($response->json('data')))->toEqualCanonicalizing(['token', 'expires_at']);

    $user = User::where('clerk_user_id', 'user_ad_reward_mint')->firstOrFail();
    expect(AdRewardSession::where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejects minting once the daily cap is already reached', function () {
    $user = authAs('user_ad_reward_capped');
    AdRewardSession::factory()->credited()->count(15)->create(['user_id' => $user->id]);

    $this->postJson(AD_REWARD_SESSIONS_ENDPOINT)->assertStatus(422);
});

it('reports todays progress toward the daily cap', function () {
    $user = authAs('user_ad_reward_progress');
    AdRewardSession::factory()->credited()->count(3)->create(['user_id' => $user->id]);

    $this->getJson(AD_REWARD_PROGRESS_ENDPOINT)
        ->assertStatus(200)
        ->assertJsonPath('data.watched_today', 3)
        ->assertJsonPath('data.daily_cap', 15)
        ->assertJsonPath('data.remaining', 12)
        ->assertJsonPath('data.enabled', true)
        ->assertJsonPath('data.tokens_per_ad', 1)
        ->assertJsonPath('data.can_earn', true)
        ->assertJsonStructure(['data' => ['watched_today', 'daily_cap', 'remaining', 'next_reset_at', 'enabled', 'tokens_per_ad', 'can_earn', 'server_date']]);
});

it('rejects minting while rewarded ads are disabled by configuration', function () {
    config(['services.admob.rewarded_ads_enabled' => false]);
    authAs('user_ad_reward_disabled');

    $this->postJson(AD_REWARD_SESSIONS_ENDPOINT)
        ->assertStatus(503)
        ->assertJson(['success' => false, 'message' => 'Rewarded ads are not available right now.']);
});
