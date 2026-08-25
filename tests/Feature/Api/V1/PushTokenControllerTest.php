<?php

use App\Models\PushToken;
use App\Models\User;
use Tests\Support\FakesClerk;

const API_V1_PUSH_TOKENS_ENDPOINT = '/api/v1/push-tokens';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects push token requests with no bearer token', function () {
    $this->postJson(API_V1_PUSH_TOKENS_ENDPOINT, ['token' => 'abc', 'platform' => 'ios'])->assertStatus(401);
    $this->deleteJson(API_V1_PUSH_TOKENS_ENDPOINT)->assertStatus(401);
});

it('validates the push token payload', function () {
    $token = $this->clerkToken(['sub' => 'user_push_invalid']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PUSH_TOKENS_ENDPOINT, ['token' => '', 'platform' => 'windows'])
        ->assertStatus(422);
});

it('registers a push token for the authenticated user', function () {
    $token = $this->clerkToken(['sub' => 'user_push_register']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PUSH_TOKENS_ENDPOINT, ['token' => 'device-token-1', 'platform' => 'ios'])
        ->assertStatus(200)
        ->assertJsonPath('data.platform', 'ios');

    $user = User::where('clerk_user_id', 'user_push_register')->firstOrFail();

    expect(PushToken::where('user_id', $user->id)->where('token', 'device-token-1')->exists())->toBeTrue();
    expect($response->json('data'))->not->toHaveKey('token');
});

it('replaces an existing token when the same user registers again', function () {
    $token = $this->clerkToken(['sub' => 'user_push_replace']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PUSH_TOKENS_ENDPOINT, ['token' => 'device-token-old', 'platform' => 'android'])
        ->assertStatus(200);

    $user = User::where('clerk_user_id', 'user_push_replace')->firstOrFail();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PUSH_TOKENS_ENDPOINT, ['token' => 'device-token-new', 'platform' => 'ios'])
        ->assertStatus(200)
        ->assertJsonPath('data.platform', 'ios');

    expect(PushToken::where('user_id', $user->id)->count())->toBe(1);
    expect(PushToken::where('user_id', $user->id)->first()->token)->toBe('device-token-new');
});

it('unregisters the push token for the authenticated user', function () {
    $token = $this->clerkToken(['sub' => 'user_push_unregister']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PUSH_TOKENS_ENDPOINT, ['token' => 'device-token', 'platform' => 'android'])
        ->assertStatus(200);

    $user = User::where('clerk_user_id', 'user_push_unregister')->firstOrFail();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(API_V1_PUSH_TOKENS_ENDPOINT)
        ->assertStatus(200);

    expect(PushToken::where('user_id', $user->id)->exists())->toBeFalse();
});

it('does not fail unregistering when no token is registered', function () {
    $token = $this->clerkToken(['sub' => 'user_push_noop']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(API_V1_PUSH_TOKENS_ENDPOINT)
        ->assertStatus(200);
});
