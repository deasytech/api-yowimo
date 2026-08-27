<?php

namespace App\Listeners;

use App\Events\FriendRequestSent;
use App\Models\Friendship;
use App\Models\User;
use App\Notifications\FriendRequestSentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFriendRequestSentPushNotification implements ShouldQueue
{
    public function handle(FriendRequestSent $event): void
    {
        $friendship = Friendship::find($event->friendshipId);
        $sender = User::find($event->senderId);
        $receiver = User::find($event->receiverId);

        if (! $friendship || ! $sender || ! $receiver) {
            return;
        }

        $receiver->notify(new FriendRequestSentNotification($friendship, $sender));
    }
}
