<?php

namespace App\Notifications\Channels;

use App\Models\Notification as NotificationModel;
use App\Models\User;
use Illuminate\Notifications\Notification;

class InAppChannel
{
    public function send(User $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toInApp')) {
            return;
        }

        $payload = $notification->toInApp($notifiable);

        NotificationModel::create([
            'user_id' => $notifiable->id,
            'title' => $payload['title'],
            'body' => $payload['body'],
            'type' => $payload['type'],
            'metadata' => $payload['metadata'] ?? null,
        ]);
    }
}
