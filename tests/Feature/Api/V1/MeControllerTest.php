<?php

use App\Models\User;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects requests with no bearer token', function () {
    $this->getJson('/api/v1/me')
        ->assertStatus(401)
        ->assertJson([
            'success' => false,
            'message' => 'Unauthenticated.',
        ]);
});

it('rejects requests with an invalid bearer token', function () {
    $this->withHeader('Authorization', 'Bearer not-a-real-token')
        ->getJson('/api/v1/me')
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
        ->getJson('/api/v1/me')
        ->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Profile retrieved successfully.',
        ]);

    $response->assertJsonPath('data.email', 'player@yowimo.app');
    $response->assertJsonPath('data.name', 'Player One');

    expect(User::query()->count())->toBe(1);
    expect(User::query()->first()->clerk_id)->toBe('user_abc123');
});

it('resolves the same internal user on subsequent requests for the same clerk id', function () {
    $token = $this->clerkToken(['sub' => 'user_same']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/me')->assertOk();
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/me')->assertOk();

    expect(User::query()->count())->toBe(1);
});

it('updates the authenticated users profile', function () {
    $token = $this->clerkToken(['sub' => 'user_update']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/me', ['username' => 'partyhost99'])
        ->assertStatus(200)
        ->assertJsonPath('data.username', 'partyhost99');

    expect(User::where('clerk_id', 'user_update')->first()->username)->toBe('partyhost99');
});

it('rejects profile updates that fail validation with the standard error envelope', function () {
    $token = $this->clerkToken(['sub' => 'user_invalid']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson('/api/v1/me', ['username' => 'a'])
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
        ->patchJson('/api/v1/me', ['username' => 'taken'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('username');
});
