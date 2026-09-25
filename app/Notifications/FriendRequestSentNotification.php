<?php

namespace App\Notifications;

use App\Models\Friendship;
use App\Models\User;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FriendRequestSentNotification extends Notification implements ShouldQueue
{
    use DeliversViaFcmAndInApp, Queueable;

    public function __construct(
        public readonly Friendship $friendship,
        public readonly User $sender,
    ) {}

    /**
     * @return array<string, mixed>
     */
    private function payload(): array // NOSONAR php:S1144 - satisfies DeliversViaFcmAndInApp::payload(), called via $this->payload() in the trait
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
