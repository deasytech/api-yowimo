<?php

namespace App\Listeners;

use App\Events\FriendRequestAccepted;
use App\Models\Friendship;
use App\Models\User;
use App\Notifications\FriendRequestAcceptedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendFriendRequestAcceptedPushNotification implements ShouldQueue
{
    public function handle(FriendRequestAccepted $event): void
    {
        $friendship = Friendship::find($event->friendshipId);
        $sender = User::find($event->senderId);
        $receiver = User::find($event->receiverId);

        if (! $friendship || ! $sender || ! $receiver) {
            return;
        }

        $sender->notify(new FriendRequestAcceptedNotification($friendship, $receiver));
    }
}
