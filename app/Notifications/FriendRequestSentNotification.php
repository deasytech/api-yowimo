<?php

namespace App\Notifications;

use App\Models\Friendship;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\Channels\InAppChannel;
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
        return [FcmChannel::class, InAppChannel::class];
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

    /**
     * @return array<string, mixed>
     */
    public function toInApp(object $notifiable): array
    {
        $senderName = $this->sender->display_name ?: $this->sender->username;

        return [
            'title' => 'New friend request',
            'body' => "{$senderName} sent you a friend request.",
            'type' => 'friend.request.sent',
            'metadata' => [
                'friendship_id' => $this->friendship->id,
                'sender_id' => $this->sender->id,
            ],
        ];
    }
}
