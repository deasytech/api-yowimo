<?php

namespace App\Notifications\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Contract\Messaging;

class FcmChannel
{
    public function send(User $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $token = $notifiable->pushToken?->token;

        if (! $token) {
            return;
        }

        // Resolved lazily, and only once we know there's a token to send to,
        // so that users without a registered device never require Firebase
        // to be configured/reachable.
        app(Messaging::class)->send($notification->toFcm($notifiable)->withToken($token));
    }
}
