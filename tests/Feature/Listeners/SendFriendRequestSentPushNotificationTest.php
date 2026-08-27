<?php

use App\Listeners\SendFriendRequestSentPushNotification;
use App\Models\User;
use App\Notifications\FriendRequestSentNotification;
use App\Services\Friends\FriendshipService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('pushes the friend-request-sent notification listener onto the queue when FriendRequestSent fires', function () {
    Queue::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    app(FriendshipService::class)->send($sender, $receiver);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendFriendRequestSentPushNotification::class);
});

it('notifies the receiver when a friend request is sent', function () {
    Notification::fake();

    $sender = User::factory()->create();
    $receiver = User::factory()->create();

    $friendship = app(FriendshipService::class)->send($sender, $receiver);

    Notification::assertSentTo($receiver, FriendRequestSentNotification::class, fn ($notification) => $notification->friendship->id === $friendship->id && $notification->sender->id === $sender->id);
    Notification::assertNothingSentTo($sender);
});
