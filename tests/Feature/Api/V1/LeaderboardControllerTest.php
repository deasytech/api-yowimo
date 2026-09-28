<?php

use App\Models\User;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects leaderboard requests with no bearer token', function () {
    $this->getJson('/api/v1/leaderboards')->assertStatus(401);
});

it('lists users ordered by xp, highest first', function () {
    $token = $this->clerkToken(['sub' => 'leaderboard_viewer']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    $top = User::factory()->create(['xp' => 500]);
    $middle = User::factory()->create(['xp' => 200]);
    $bottom = User::factory()->create(['xp' => 0]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/leaderboards')
        ->assertStatus(200);

    $userIds = collect($response->json('data'))->pluck('user.id')->all();
    $topIndex = array_search($top->id, $userIds, true);
    $middleIndex = array_search($middle->id, $userIds, true);
    $bottomIndex = array_search($bottom->id, $userIds, true);

    expect($topIndex)->not->toBeFalse()
        ->and($middleIndex)->not->toBeFalse()
        ->and($bottomIndex)->not->toBeFalse()
        ->and($topIndex)->toBeLessThan($middleIndex)
        ->and($middleIndex)->toBeLessThan($bottomIndex);
});

it('excludes deleted accounts from the leaderboard', function () {
    $token = $this->clerkToken(['sub' => 'leaderboard_viewer_excludes_deleted']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    $deleted = User::factory()->create(['xp' => 9999]);
    $deleted->delete();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/leaderboards')
        ->assertStatus(200);

    $userIds = collect($response->json('data'))->pluck('user.id')->all();
    expect($userIds)->not->toContain($deleted->id);
});
