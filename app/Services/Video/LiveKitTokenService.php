<?php

namespace App\Services\Video;

use App\Exceptions\Api\LiveKitNotConfiguredException;
use App\Models\Party;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Support\Str;

/**
 * Mints LiveKit access tokens directly (no SDK package — signed with
 * firebase/php-jwt, already a dependency for Clerk verification), mirroring
 * PaystackClient/OpenAiProvider's "lean HTTP-facade, no SDK" pattern.
 *
 * v0 scope: one room per party, everyone can publish and subscribe (no host
 * mute/spotlight controls), no chat (LiveKit data channel unused), no
 * recording. No LiveKit server API calls are made at all — a room is
 * created automatically by LiveKit itself when the first participant joins
 * with a valid roomJoin grant, and closes automatically once the last
 * participant leaves, so there is nothing for this app to provision or tear
 * down.
 */
class LiveKitTokenService
{
    /**
     * A party's game session can run long; issuing one longer-lived token
     * up front is simpler than building a refresh flow for v0.
     */
    private const TOKEN_TTL_SECONDS = 21600; // 6 hours

    public function roomNameFor(Party $party): string
    {
        return "party-{$party->id}";
    }

    /**
     * @throws LiveKitNotConfiguredException
     */
    public function tokenFor(User $user, Party $party): string
    {
        $apiKey = config('services.livekit.api_key');
        $apiSecret = config('services.livekit.api_secret');

        // LIVEKIT_URL is required too — a token with nowhere to connect
        // (the controller returns it alongside this token) would otherwise
        // look like a 200 success while the client has no usable room.
        if (! $apiKey || ! $apiSecret || ! config('services.livekit.url')) {
            throw new LiveKitNotConfiguredException;
        }

        $now = time();

        $payload = [
            'iss' => $apiKey,
            'sub' => (string) $user->id,
            'name' => $user->display_name ?? $user->username ?? "Player {$user->id}",
            'nbf' => $now,
            'exp' => $now + self::TOKEN_TTL_SECONDS,
            'jti' => (string) Str::uuid(),
            'video' => [
                'room' => $this->roomNameFor($party),
                'roomJoin' => true,
                'canPublish' => true,
                'canSubscribe' => true,
                // Explicit, not omitted: chat rides LiveKit's data channel,
                // and v0 doesn't build chat — an omitted grant shouldn't be
                // left to whatever LiveKit's own default happens to be.
                'canPublishData' => false,
            ],
        ];

        return JWT::encode($payload, $apiSecret, 'HS256');
    }
}
