<?php

namespace Tests\Support;

use App\Models\AdRewardSession;
use App\Models\User;
use Illuminate\Support\Str;

trait CreatesAdRewardSessions
{
    /**
     * An already-expired session with a known plaintext token, so a test can
     * drive a verification attempt against it directly.
     *
     * @return array{session: AdRewardSession, plaintext: string}
     */
    protected function expiredAdRewardSession(User $user): array
    {
        $plaintext = Str::random(64);

        $session = AdRewardSession::factory()->expired()->create([
            'user_id' => $user->id,
            'token_hash' => AdRewardSession::hashToken($plaintext),
        ]);

        return ['session' => $session, 'plaintext' => $plaintext];
    }

    /**
     * Saturates $user's daily cap with 15 already-credited sessions, then
     * creates one more structurally valid pending session with a known
     * plaintext token — simulating a session minted before the 15th credit
     * landed, arriving only after the cap was already reached.
     *
     * @return array{session: AdRewardSession, plaintext: string}
     */
    protected function pendingAdRewardSessionOverDailyCap(User $user): array
    {
        AdRewardSession::factory()->credited()->count(15)->create(['user_id' => $user->id]);

        $plaintext = Str::random(64);

        $session = AdRewardSession::factory()->create([
            'user_id' => $user->id,
            'token_hash' => AdRewardSession::hashToken($plaintext),
        ]);

        return ['session' => $session, 'plaintext' => $plaintext];
    }
}
