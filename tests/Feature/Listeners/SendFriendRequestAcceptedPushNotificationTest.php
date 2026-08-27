<?php

use App\Listeners\SendFriendRequestAcceptedPushNotification;
use App\Models\Friendship;
use App\Models\User;
use App\Notifications\FriendRequestAcceptedNotification;
use App\Services\Friends\FriendshipService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('pushes the friend-request-accepted notification listener onto the queue when FriendRequestAccepted fires', function () {
    Queue::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $friendship = Friendship::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    app(FriendshipService::class)->accept($friendship);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendFriendRequestAcceptedPushNotification::class);
});

it('notifies the original sender when their friend request is accepted', function () {
    Notification::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $friendship = Friendship::factory()->create(['sender_id' => $sender->id, 'receiver_id' => $receiver->id]);

    app(FriendshipService::class)->accept($friendship);

    Notification::assertSentTo($sender, FriendRequestAcceptedNotification::class, fn ($notification) => $notification->friendship->id === $friendship->id && $notification->accepter->id === $receiver->id);
    Notification::assertNothingSentTo($receiver);
});
