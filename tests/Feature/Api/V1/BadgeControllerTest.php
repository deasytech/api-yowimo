<?php

use App\Enums\BadgeKey;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Tests\Support\FakesClerk;

const API_V1_BADGES_ENDPOINT = '/api/v1/badges';
const API_V1_MY_BADGES_ENDPOINT = '/api/v1/users/me/badges';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects requests with no bearer token', function () {
    $this->getJson(API_V1_BADGES_ENDPOINT)->assertStatus(401);
    $this->getJson(API_V1_MY_BADGES_ENDPOINT)->assertStatus(401);
});

it('lists the badge catalog', function () {
    $token = $this->clerkToken();

    Badge::factory()->create(['key' => BadgeKey::FirstParty, 'name' => 'First Party']);
    Badge::factory()->create(['key' => BadgeKey::PartyKing, 'name' => 'Party King']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_BADGES_ENDPOINT)
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($response->json('data'))->toHaveCount(2);
    expect($response->json('meta'))->toHaveKeys(['per_page', 'has_more_pages', 'next_cursor', 'prev_cursor']);
});

it("lists only the authenticated user's earned badges, newest first", function () {
    $token = $this->clerkToken(['sub' => 'user_my_badges_owner']);
    app('auth')->forgetGuards();
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'user_my_badges_owner')->firstOrFail();

    $otherUser = User::factory()->create();

    $firstBadge = Badge::factory()->create(['key' => BadgeKey::FirstParty, 'name' => 'First Party']);
    $secondBadge = Badge::factory()->create(['key' => BadgeKey::PartyKing, 'name' => 'Party King']);

    UserBadge::create(['user_id' => $user->id, 'badge_id' => $firstBadge->id, 'earned_at' => now()->subDay()]);
    UserBadge::create(['user_id' => $user->id, 'badge_id' => $secondBadge->id, 'earned_at' => now()]);
    UserBadge::create(['user_id' => $otherUser->id, 'badge_id' => $firstBadge->id, 'earned_at' => now()]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_MY_BADGES_ENDPOINT)
        ->assertStatus(200)
        ->assertJson(['success' => true]);

    expect($response->json('data'))->toHaveCount(2);
    $response->assertJsonPath('data.0.badge.name', 'Party King');
    $response->assertJsonPath('data.1.badge.name', 'First Party');
});
