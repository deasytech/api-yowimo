<?php

namespace App\Notifications;

use App\Models\Friendship;
use App\Models\User;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class FriendRequestAcceptedNotification extends Notification implements ShouldQueue
{
    use DeliversViaFcmAndInApp, Queueable;

    public function __construct(
        public readonly Friendship $friendship,
        public readonly User $accepter,
    ) {}

    /**
     * Shared title/body/type/metadata for both the FCM and in-app channels —
     * kept in one place so the two deliveries can't drift on content, only on
     * how each channel formats/casts it (FCM's `withData()` requires strings).
     *
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $accepterName = $this->accepter->display_name ?: $this->accepter->username;

        return [
            'title' => 'Friend request accepted',
            'body' => "{$accepterName} accepted your friend request.",
            'type' => 'friend.request.accepted',
            'metadata' => [
                'friendship_id' => $this->friendship->id,
                'accepter_id' => $this->accepter->id,
            ],
        ];
    }
}
