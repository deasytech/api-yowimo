<?php

use App\Models\Badge;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserBadge;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function publicProfileEndpoint(User|int $user): string
{
    $id = $user instanceof User ? $user->id : $user;

    return "/api/v1/users/{$id}";
}

it('rejects viewing a profile with no bearer token', function () {
    $this->getJson(publicProfileEndpoint(User::factory()->create()))->assertStatus(401);
});

it('returns only public-safe fields for another user', function () {
    authAs('profile_viewer_fields');
    $user = User::factory()->create([
        'username' => 'publicname',
        'display_name' => 'Public Name',
        'email' => 'private@example.com',
        'xp' => 120,
    ]);

    $response = $this->getJson(publicProfileEndpoint($user))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.username', 'publicname')
        ->assertJsonPath('data.display_name', 'Public Name')
        ->assertJsonPath('data.xp', 120)
        ->assertJsonPath('data.friendship.status', 'none')
        ->assertJsonPath('data.friendship.id', null);

    expect(array_keys($response->json('data')))
        ->toEqualCanonicalizing(['id', 'username', 'display_name', 'avatar_url', 'xp', 'badges', 'friendship']);
});

it('includes the users earned badges', function () {
    authAs('profile_viewer_badges');
    $user = User::factory()->create();
    $badge = Badge::factory()->create();
    UserBadge::factory()->create(['user_id' => $user->id, 'badge_id' => $badge->id]);

    $this->getJson(publicProfileEndpoint($user))
        ->assertOk()
        ->assertJsonCount(1, 'data.badges')
        ->assertJsonPath('data.badges.0.badge.id', $badge->id);
});

it('reports the friendship status from the viewers perspective', function (string $relation, string $expected) {
    $viewer = authAs("profile_viewer_{$relation}");
    $user = User::factory()->create();

    $friendship = match ($relation) {
        'friends' => Friendship::factory()->accepted()->create(['sender_id' => $user->id, 'receiver_id' => $viewer->id]),
        'sent' => Friendship::factory()->create(['sender_id' => $viewer->id, 'receiver_id' => $user->id]),
        'received' => Friendship::factory()->create(['sender_id' => $user->id, 'receiver_id' => $viewer->id]),
    };

    $this->getJson(publicProfileEndpoint($user))
        ->assertOk()
        ->assertJsonPath('data.friendship.status', $expected)
        ->assertJsonPath('data.friendship.id', $friendship->id);
})->with([
    ['friends', 'friends'],
    ['sent', 'request_sent'],
    ['received', 'request_received'],
]);

it('reports self when viewing your own profile', function () {
    $viewer = authAs('profile_viewer_self');

    $this->getJson(publicProfileEndpoint($viewer))
        ->assertOk()
        ->assertJsonPath('data.friendship.status', 'self');
});

it('returns 404 when either user has blocked the other', function (bool $viewerIsBlocker) {
    $viewer = authAs('profile_viewer_blocked_'.($viewerIsBlocker ? 'blocker' : 'blocked'));
    $user = User::factory()->create();

    BlockedUser::factory()->create($viewerIsBlocker
        ? ['blocker_id' => $viewer->id, 'blocked_id' => $user->id]
        : ['blocker_id' => $user->id, 'blocked_id' => $viewer->id]);

    $this->getJson(publicProfileEndpoint($user))->assertStatus(404);
})->with([
    'viewer blocked the user' => [true],
    'user blocked the viewer' => [false],
]);

it('returns 404 for a deleted, deactivated, or nonexistent user', function () {
    authAs('profile_viewer_missing');
    $deleted = User::factory()->create();
    $deleted->delete();
    $deactivated = User::factory()->deactivated()->create();

    $this->getJson(publicProfileEndpoint($deleted))->assertStatus(404);
    $this->getJson(publicProfileEndpoint($deactivated))->assertStatus(404);
    $this->getJson(publicProfileEndpoint(999999))->assertStatus(404);
});

it('keeps GET /users/me returning the authenticated users own profile', function () {
    $viewer = authAs('profile_viewer_me');

    $this->getJson(API_V1_ME_ENDPOINT)
        ->assertOk()
        ->assertJsonPath('data.id', $viewer->id)
        ->assertJsonStructure(['data' => ['email', 'wallet']]);
});
