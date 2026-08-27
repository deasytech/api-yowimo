<?php

namespace App\Notifications;

use App\Models\Friendship;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FriendRequestAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Friendship $friendship,
        public readonly User $accepter,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class];
    }

    public function toFcm(object $notifiable): CloudMessage
    {
        $accepterName = $this->accepter->display_name ?: $this->accepter->username;

        return CloudMessage::new()
            ->withNotification(FcmNotification::create(
                'Friend request accepted',
                "{$accepterName} accepted your friend request.",
            ))
            ->withData([
                'type' => 'friend.request.accepted',
                'friendship_id' => (string) $this->friendship->id,
                'accepter_id' => (string) $this->accepter->id,
            ]);
    }
}
