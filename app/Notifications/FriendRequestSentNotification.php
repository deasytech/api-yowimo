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

class FriendRequestSentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Friendship $friendship,
        public readonly User $sender,
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
        $senderName = $this->sender->display_name ?: $this->sender->username;

        return CloudMessage::new()
            ->withNotification(FcmNotification::create(
                'New friend request',
                "{$senderName} sent you a friend request.",
            ))
            ->withData([
                'type' => 'friend.request.sent',
                'friendship_id' => (string) $this->friendship->id,
                'sender_id' => (string) $this->sender->id,
            ]);
    }
}
