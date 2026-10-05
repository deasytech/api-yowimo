<?php

use App\Enums\PartyStatus;
use App\Models\Badge;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\Party;
use App\Models\PartyMember;
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
        'bio' => 'Here for the chaos.',
        'interests' => ['music', 'trivia'],
        'country_code' => 'GH',
    ]);

    $response = $this->getJson(publicProfileEndpoint($user))
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.username', 'publicname')
        ->assertJsonPath('data.display_name', 'Public Name')
        ->assertJsonPath('data.bio', 'Here for the chaos.')
        ->assertJsonPath('data.interests', ['music', 'trivia'])
        ->assertJsonPath('data.country_code', 'GH')
        ->assertJsonPath('data.xp', 120)
        ->assertJsonPath('data.friendship.status', 'none')
        ->assertJsonPath('data.friendship.id', null);

    expect(array_keys($response->json('data')))
        ->toEqualCanonicalizing(['id', 'username', 'display_name', 'avatar_url', 'bio', 'interests', 'country_code', 'xp', 'badges', 'stats', 'friendship']);
});

it("returns the viewed user's stats, not the viewers", function () {
    $viewer = authAs('profile_viewer_stats_viewer');
    $user = User::factory()->create();
    $otherHost = User::factory()->create();

    // The viewer is friends with and has joined/hosted parties of their own —
    // none of this should leak into the viewed user's counts.
    Friendship::factory()->accepted()->create(['sender_id' => $viewer->id, 'receiver_id' => User::factory()->create()->id]);
    $viewerHostedParty = Party::factory()->create(['host_id' => $viewer->id, 'status' => PartyStatus::Ended]);
    PartyMember::factory()->create(['party_id' => $viewerHostedParty->id, 'user_id' => $viewer->id]);

    // The viewed user: 2 accepted friends, 1 pending (doesn't count), 2
    // ended parties joined (not hosted), 1 still-draft party they hosted
    // (counts toward created, since the frontend wants to see their own
    // in-progress parties here — unlike the ended-only "joined" count).
    Friendship::factory()->accepted()->create(['sender_id' => $user->id, 'receiver_id' => User::factory()->create()->id]);
    Friendship::factory()->accepted()->create(['sender_id' => User::factory()->create()->id, 'receiver_id' => $user->id]);
    Friendship::factory()->create(['sender_id' => $user->id, 'receiver_id' => User::factory()->create()->id]);

    $joined1 = Party::factory()->create(['host_id' => $otherHost->id, 'status' => PartyStatus::Ended]);
    PartyMember::factory()->create(['party_id' => $joined1->id, 'user_id' => $user->id]);
    $joined2 = Party::factory()->create(['host_id' => $otherHost->id, 'status' => PartyStatus::Ended]);
    PartyMember::factory()->create(['party_id' => $joined2->id, 'user_id' => $user->id]);
    // A joined party that hasn't ended yet — shouldn't count.
    $stillLive = Party::factory()->create(['host_id' => $otherHost->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $stillLive->id, 'user_id' => $user->id]);

    Party::factory()->create(['host_id' => $user->id, 'status' => PartyStatus::Draft]);
    Party::factory()->create(['host_id' => $user->id, 'status' => PartyStatus::Ended]);

    $this->getJson(publicProfileEndpoint($user))
        ->assertOk()
        ->assertJsonPath('data.stats.friends_count', 2)
        ->assertJsonPath('data.stats.parties_joined_count', 2)
        ->assertJsonPath('data.stats.parties_created_count', 2);
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
