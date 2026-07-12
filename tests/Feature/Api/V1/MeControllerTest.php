<?php

use App\Models\User;
use Tests\Support\FakesClerk;

const API_V1_ME_ENDPOINT = '/api/v1/users/me';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects requests with no bearer token', function () {
    $this->getJson(API_V1_ME_ENDPOINT)
        ->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});

it('rejects requests with an invalid bearer token', function () {
    $this->withHeader('Authorization', 'Bearer not-a-real-token')
        ->getJson(API_V1_ME_ENDPOINT)
        ->assertStatus(401)
        ->assertJson(['success' => false]);
});

it('just-in-time provisions a new internal user from a verified clerk token', function () {
    $token = $this->clerkToken([
        'sub' => 'user_abc123',
        'email' => 'player@yowimo.app',
        'name' => 'Player One',
    ]);

    expect(User::query()->count())->toBe(0);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_ME_ENDPOINT)
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
        ]);

    $response->assertJsonPath('data.email', 'player@yowimo.app');
    $response->assertJsonPath('data.display_name', 'Player One');
    $response->assertJsonPath('data.status', 'active');
    $response->assertJsonPath('data.wallet.enabled', false);

    expect(User::query()->count())->toBe(1);
    expect(User::query()->first()->clerk_user_id)->toBe('user_abc123');
});

it('resolves the same internal user on subsequent requests for the same clerk id', function () {
    $token = $this->clerkToken(['sub' => 'user_same']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();

    expect(User::query()->count())->toBe(1);
});

it('rejects a valid token belonging to a deactivated user', function () {
    $token = $this->clerkToken(['sub' => 'user_gone']);

    User::factory()->deactivated()->create(['clerk_user_id' => 'user_gone']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_ME_ENDPOINT)
        ->assertStatus(401);
});

it('updates the authenticated users profile', function () {
    $token = $this->clerkToken(['sub' => 'user_update']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_ME_ENDPOINT, [
            'username' => 'partyhost99',
            'bio' => 'Here for the games.',
            'country_code' => 'GH',
            'interests' => ['music', 'trivia'],
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.username', 'partyhost99')
        ->assertJsonPath('data.bio', 'Here for the games.')
        ->assertJsonPath('data.country_code', 'GH')
        ->assertJsonPath('data.interests', ['music', 'trivia']);

    expect(User::where('clerk_user_id', 'user_update')->first()->username)->toBe('partyhost99');
});

it('rejects profile updates that fail validation with the standard error envelope', function () {
    $token = $this->clerkToken(['sub' => 'user_invalid']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_ME_ENDPOINT, ['username' => 'a'])
        ->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed',
        ])
        ->assertJsonValidationErrors('username');
});

it('does not allow taking a username already used by another user', function () {
    User::factory()->create(['username' => 'taken']);
    $token = $this->clerkToken(['sub' => 'user_conflict']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_ME_ENDPOINT, ['username' => 'taken'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('username');
});
