<?php

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function authAs(string $clerkSub): User
{
    $token = test()->clerkToken(['sub' => $clerkSub]);
    test()->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    return User::where('clerk_user_id', $clerkSub)->firstOrFail();
}

it('rejects sending a friend request with no bearer token', function () {
    $this->postJson('/api/v1/friend-requests', ['receiver_id' => User::factory()->create()->id])
        ->assertStatus(401);
});

it('sends a friend request to another user', function () {
    $sender = authAs('friend_sender_1');
    $receiver = User::factory()->create();

    $this->postJson('/api/v1/friend-requests', ['receiver_id' => $receiver->id])
        ->assertStatus(201)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.sender.id', $sender->id)
        ->assertJsonPath('data.receiver.id', $receiver->id);

    expect(Friendship::where('sender_id', $sender->id)->where('receiver_id', $receiver->id)->where('status', FriendshipStatus::Pending)->exists())->toBeTrue();
});

it('rejects sending a friend request to yourself', function () {
    $sender = authAs('friend_sender_self');

    $this->postJson('/api/v1/friend-requests', ['receiver_id' => $sender->id])
        ->assertStatus(422);
});

it('rejects a duplicate pending friend request in either direction', function () {
    $sender = authAs('friend_sender_dup');
    $receiver = User::factory()->create();

    Friendship::factory()->create([
        'sender_id' => $receiver->id,
        'receiver_id' => $sender->id,
        'status' => FriendshipStatus::Pending,
    ]);

    $this->postJson('/api/v1/friend-requests', ['receiver_id' => $receiver->id])
        ->assertStatus(409);
});

it('rejects sending a friend request to an existing friend', function () {
    $sender = authAs('friend_sender_already');
    $receiver = User::factory()->create();

    Friendship::factory()->accepted()->create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
    ]);

    $this->postJson('/api/v1/friend-requests', ['receiver_id' => $receiver->id])
        ->assertStatus(409);
});

it('lets the receiver accept a pending friend request', function () {
    $receiver = authAs('friend_receiver_accept');
    $sender = User::factory()->create();
    $friendship = Friendship::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    $this->postJson("/api/v1/friend-requests/{$friendship->id}/accept")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'accepted');

    $friendship->refresh();
    expect($friendship->status)->toBe(FriendshipStatus::Accepted);
    expect($friendship->accepted_at)->not->toBeNull();
});

it('forbids the sender from accepting their own outgoing request', function () {
    $sender = authAs('friend_sender_cant_accept');
    $receiver = User::factory()->create();
    $friendship = Friendship::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    $this->postJson("/api/v1/friend-requests/{$friendship->id}/accept")
        ->assertStatus(403);
});

it('rejects accepting a request that is no longer pending', function () {
    $receiver = authAs('friend_receiver_accept_twice');
    $sender = User::factory()->create();
    $friendship = Friendship::factory()->accepted()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    $this->postJson("/api/v1/friend-requests/{$friendship->id}/accept")
        ->assertStatus(422);
});

it('lets the receiver reject a pending friend request', function () {
    $receiver = authAs('friend_receiver_reject');
    $sender = User::factory()->create();
    $friendship = Friendship::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    $this->postJson("/api/v1/friend-requests/{$friendship->id}/reject")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'rejected');

    expect($friendship->fresh()->status)->toBe(FriendshipStatus::Rejected);
});

it('forbids the sender from rejecting their own outgoing request', function () {
    $sender = authAs('friend_sender_cant_reject');
    $receiver = User::factory()->create();
    $friendship = Friendship::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    $this->postJson("/api/v1/friend-requests/{$friendship->id}/reject")
        ->assertStatus(403);
});

it('lets the sender cancel their own pending outgoing request', function () {
    $sender = authAs('friend_sender_cancel');
    $receiver = User::factory()->create();
    $friendship = Friendship::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    $this->deleteJson("/api/v1/friend-requests/{$friendship->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');

    expect($friendship->fresh()->status)->toBe(FriendshipStatus::Cancelled);
});

it('forbids the receiver from cancelling an incoming request', function () {
    $receiver = authAs('friend_receiver_cant_cancel');
    $sender = User::factory()->create();
    $friendship = Friendship::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    $this->deleteJson("/api/v1/friend-requests/{$friendship->id}")
        ->assertStatus(403);
});

it('lets either side unfriend an accepted friendship', function () {
    $user = authAs('friend_unfriend_receiver');
    $other = User::factory()->create();
    $friendship = Friendship::factory()->accepted()->create(['sender_id' => $other->id, 'receiver_id' => $user->id]);

    $this->deleteJson("/api/v1/friends/{$friendship->id}")
        ->assertStatus(200);

    expect($friendship->fresh()->status)->toBe(FriendshipStatus::Removed);
});

it('forbids an unrelated user from unfriending', function () {
    authAs('friend_unrelated');
    $a = User::factory()->create();
    $b = User::factory()->create();
    $friendship = Friendship::factory()->accepted()->create(['sender_id' => $a->id, 'receiver_id' => $b->id]);

    $this->deleteJson("/api/v1/friends/{$friendship->id}")
        ->assertStatus(403);
});

it('rejects unfriending a friendship that is not accepted', function () {
    $receiver = authAs('friend_unfriend_not_accepted');
    $sender = User::factory()->create();
    $friendship = Friendship::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    $this->deleteJson("/api/v1/friends/{$friendship->id}")
        ->assertStatus(422);
});

it('lists accepted friends from either side', function () {
    $user = authAs('friend_list_user');
    $asSender = User::factory()->create();
    $asReceiver = User::factory()->create();
    $pending = User::factory()->create();

    Friendship::factory()->accepted()->create(['sender_id' => $user->id, 'receiver_id' => $asSender->id]);
    Friendship::factory()->accepted()->create(['sender_id' => $asReceiver->id, 'receiver_id' => $user->id]);
    Friendship::factory()->create(['sender_id' => $user->id, 'receiver_id' => $pending->id]);

    $response = $this->getJson('/api/v1/friends')->assertStatus(200);

    $friendIds = collect($response->json('data'))->pluck('friend.id')->all();
    expect($friendIds)->toEqualCanonicalizing([$asSender->id, $asReceiver->id]);
});

it('lists pending friend requests, both incoming and outgoing', function () {
    $user = authAs('friend_pending_list_user');
    $incomingFrom = User::factory()->create();
    $outgoingTo = User::factory()->create();

    Friendship::factory()->create(['sender_id' => $incomingFrom->id, 'receiver_id' => $user->id]);
    Friendship::factory()->create(['sender_id' => $user->id, 'receiver_id' => $outgoingTo->id]);

    $response = $this->getJson('/api/v1/friend-requests')->assertStatus(200);

    expect($response->json('data'))->toHaveCount(2);
});
