<?php

namespace App\Services\Notifications;

use App\Models\PushToken;
use App\Models\User;

class PushTokenService
{
    /**
     * Register (or replace) the user's push token. A user has at most one
     * active token — the newest registration replaces any previous one.
     */
    public function register(User $user, string $token, string $platform): PushToken
    {
        return PushToken::updateOrCreate(
            ['user_id' => $user->id],
            ['token' => $token, 'platform' => $platform],
        );
    }

    public function unregister(User $user): void
    {
        PushToken::where('user_id', $user->id)->delete();
    }
}
