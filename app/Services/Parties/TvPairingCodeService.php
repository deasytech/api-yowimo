<?php

namespace App\Services\Parties;

use App\Models\Party;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class TvPairingCodeService
{
    /**
     * Numeric so it's easy to key in on a TV remote.
     */
    private const CHARSET = '0123456789';

    private const LENGTH = 6;

    private const TTL_MINUTES = 10;

    /**
     * Returns the party's current pairing code, generating a fresh one if
     * none exists yet or the last one has expired. Cached rather than
     * persisted — this is a short-lived display code, not durable state.
     *
     * The cache miss path is lock-guarded so two concurrent requests for the
     * same party (e.g. the host's screen polling while another device also
     * asks) can't each generate a different code and race on which one ends
     * up cached — whichever request gets there first under the lock decides
     * the code the other one is handed back too.
     *
     * @return array{code: string, expires_at: Carbon}
     */
    public function forParty(Party $party): array
    {
        $key = $this->cacheKey($party);

        return $this->freshPairing($key) ?? Cache::lock("{$key}:lock", 10)->block(5, function () use ($key) {
            if ($pairing = $this->freshPairing($key)) {
                return $pairing;
            }

            $expiresAt = now()->addMinutes(self::TTL_MINUTES);
            $pairing = ['code' => $this->randomCode(), 'expires_at' => $expiresAt];

            Cache::put($key, $pairing, $expiresAt);

            return $pairing;
        });
    }

    /**
     * @return array{code: string, expires_at: Carbon}|null
     */
    private function freshPairing(string $key): ?array
    {
        $pairing = Cache::get($key);

        if (! $pairing || Carbon::parse($pairing['expires_at'])->isPast()) {
            return null;
        }

        return ['code' => $pairing['code'], 'expires_at' => Carbon::parse($pairing['expires_at'])];
    }

    private function cacheKey(Party $party): string
    {
        return "party:{$party->id}:tv-pairing-code";
    }

    private function randomCode(): string
    {
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::CHARSET[random_int(0, strlen(self::CHARSET) - 1)];
        }

        return $code;
    }
}
