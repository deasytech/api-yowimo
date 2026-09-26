<?php

use App\Enums\FriendshipStatus;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\User;
use Tests\Support\FakesClerk;

const API_V1_BLOCKS_ENDPOINT = '/api/v1/blocks';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects blocking with no bearer token', function () {
    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => User::factory()->create()->id])
        ->assertStatus(401);
});

it('blocks another user', function () {
    $blocker = authAs('block_blocker_1');
    $target = User::factory()->create();

    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => $target->id])
        ->assertStatus(201)
        ->assertJsonPath('data.user.id', $target->id);

    expect(BlockedUser::where('blocker_id', $blocker->id)->where('blocked_id', $target->id)->exists())->toBeTrue();
});

it('is idempotent when blocking the same user twice', function () {
    $blocker = authAs('block_blocker_twice');
    $target = User::factory()->create();

    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => $target->id])->assertStatus(201);
    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => $target->id])->assertStatus(200);

    expect(BlockedUser::where('blocker_id', $blocker->id)->count())->toBe(1);
});

it('rejects blocking yourself or a nonexistent user', function () {
    $blocker = authAs('block_blocker_self');

    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => $blocker->id])->assertStatus(422);
    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => 999999])->assertStatus(422);
});

it('rejects blocking a soft-deleted user at validation', function () {
    authAs('block_blocker_deleted_target');
    $target = User::factory()->create();
    $target->delete();

    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => $target->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('user_id');
});

it('removes an accepted friendship when blocking', function () {
    $blocker = authAs('block_blocker_friend');
    $target = User::factory()->create();
    $friendship = Friendship::factory()->accepted()->create(['sender_id' => $target->id, 'receiver_id' => $blocker->id]);

    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => $target->id])->assertStatus(201);

    expect($friendship->refresh()->status)->toBe(FriendshipStatus::Removed);
    $this->getJson('/api/v1/friends')->assertOk()->assertJsonCount(0, 'data');
});

it('closes pending requests in both directions when blocking', function () {
    $blocker = authAs('block_blocker_pending');
    $target = User::factory()->create();
    $outgoing = Friendship::factory()->create(['sender_id' => $blocker->id, 'receiver_id' => $target->id]);
    $incoming = Friendship::factory()->create(['sender_id' => $target->id, 'receiver_id' => $blocker->id]);

    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => $target->id])->assertStatus(201);

    expect($outgoing->refresh()->status)->toBe(FriendshipStatus::Cancelled);
    expect($incoming->refresh()->status)->toBe(FriendshipStatus::Rejected);
});

it('leaves friendships with other users untouched when blocking', function () {
    $blocker = authAs('block_blocker_others');
    $target = User::factory()->create();
    $bystanderFriendship = Friendship::factory()->accepted()->create(['sender_id' => $blocker->id]);

    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => $target->id])->assertStatus(201);

    expect($bystanderFriendship->refresh()->status)->toBe(FriendshipStatus::Accepted);
});

it('prevents friend requests in either direction once blocked', function () {
    $blocker = authAs('block_blocker_requests');
    $target = User::factory()->create(['clerk_user_id' => 'block_target_requests']);

    $this->postJson(API_V1_BLOCKS_ENDPOINT, ['user_id' => $target->id])->assertStatus(201);

    $this->postJson(API_V1_FRIEND_REQUESTS_ENDPOINT, ['receiver_id' => $target->id])->assertStatus(403);

    $this->app->make('auth')->forgetGuards();
    authAs('block_target_requests');

    $this->postJson(API_V1_FRIEND_REQUESTS_ENDPOINT, ['receiver_id' => $blocker->id])
        ->assertStatus(403)
        ->assertJsonPath('message', 'You cannot send a friend request to this user.');

    expect(Friendship::count())->toBe(0);
});

it('lists only the users the caller has blocked', function () {
    $blocker = authAs('block_blocker_list');
    $blocked = User::factory()->create();
    BlockedUser::factory()->create(['blocker_id' => $blocker->id, 'blocked_id' => $blocked->id]);
    BlockedUser::factory()->create(['blocked_id' => $blocker->id]);

    $this->getJson(API_V1_BLOCKS_ENDPOINT)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.id', $blocked->id);
});

it('unblocks a user, allowing friend requests again', function () {
    $blocker = authAs('block_blocker_unblock');
    $target = User::factory()->create();
    BlockedUser::factory()->create(['blocker_id' => $blocker->id, 'blocked_id' => $target->id]);

    $this->deleteJson(API_V1_BLOCKS_ENDPOINT."/{$target->id}")->assertOk();

    expect(BlockedUser::count())->toBe(0);
    $this->postJson(API_V1_FRIEND_REQUESTS_ENDPOINT, ['receiver_id' => $target->id])->assertStatus(201);
});

it('only removes the callers own block when unblocking', function () {
    $caller = authAs('block_blocker_scoped');
    $other = User::factory()->create();
    BlockedUser::factory()->create(['blocker_id' => $other->id, 'blocked_id' => $caller->id]);

    $this->deleteJson(API_V1_BLOCKS_ENDPOINT."/{$other->id}")->assertOk();

    expect(BlockedUser::where('blocker_id', $other->id)->exists())->toBeTrue();
});
