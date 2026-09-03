<?php

use App\Enums\BadgeKey;
use App\Enums\FriendshipStatus;
use App\Models\Badge;
use App\Models\Friendship;
use App\Models\User;
use App\Models\UserBadge;
use App\Services\Friends\FriendshipService;

beforeEach(function () {
    Badge::factory()->create(['key' => BadgeKey::SocialButterfly]);
});

it('awards Social Butterfly once a user reaches 10 accepted friendships', function () {
    $user = User::factory()->create();

    // 9 prior accepted friendships (mixed sender/receiver sides), seeded
    // directly to isolate the threshold check on the 10th.
    for ($i = 0; $i < 9; $i++) {
        Friendship::factory()->create([
            'sender_id' => $i % 2 === 0 ? $user->id : User::factory(),
            'receiver_id' => $i % 2 === 0 ? User::factory() : $user->id,
            'status' => FriendshipStatus::Accepted,
            'accepted_at' => now(),
        ]);
    }

    $newFriend = User::factory()->create();
    $friendship = Friendship::factory()->create([
        'sender_id' => $user->id,
        'receiver_id' => $newFriend->id,
        'status' => FriendshipStatus::Pending,
    ]);

    app(FriendshipService::class)->accept($friendship);

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::SocialButterfly))
        ->where('user_id', $user->id)->exists())->toBeTrue();
});

it('does not award Social Butterfly before the 10th accepted friendship', function () {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $friendship = Friendship::factory()->create([
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'status' => FriendshipStatus::Pending,
    ]);

    app(FriendshipService::class)->accept($friendship);

    expect(UserBadge::where('user_id', $sender->id)->exists())->toBeFalse();
    expect(UserBadge::where('user_id', $receiver->id)->exists())->toBeFalse();
});
