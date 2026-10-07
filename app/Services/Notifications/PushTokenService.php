<?php

namespace App\Services\Notifications;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PushTokenService
{
    /**
     * Register (or replace) the user's push token. A user has at most one
     * active token — the newest registration replaces any previous one.
     *
     * A device's token is stable across account switches, so if another user
     * previously registered this exact token (e.g. a shared device that
     * logged out without unregistering), that stale row is removed first —
     * otherwise a later notification for that other user would still be
     * routed to this device.
     */
    public function register(User $user, string $token, string $platform): PushToken
    {
        return DB::transaction(function () use ($user, $token, $platform) {
            // Locked first (even if no row exists yet — the lock still
            // covers that gap) so two near-simultaneous registration calls
            // for the same user are serialized here, rather than each
            // racing the delete-then-upsert below and deadlocking each
            // other on push_tokens' own-row update.
            PushToken::where('user_id', $user->id)->lockForUpdate()->first();

            PushToken::where('token', $token)
                ->where('user_id', '!=', $user->id)
                ->delete();

            return PushToken::updateOrCreate(
                ['user_id' => $user->id],
                ['token' => $token, 'platform' => $platform],
            );
        });
    }

    public function unregister(User $user): void
    {
        PushToken::where('user_id', $user->id)->delete();
    }
}
