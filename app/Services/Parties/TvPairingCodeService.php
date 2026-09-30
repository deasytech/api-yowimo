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
     * @return array{code: string, expires_at: Carbon}
     */
    public function forParty(Party $party): array
    {
        $pairing = Cache::get($this->cacheKey($party));

        if ($pairing && Carbon::parse($pairing['expires_at'])->isFuture()) {
            return ['code' => $pairing['code'], 'expires_at' => Carbon::parse($pairing['expires_at'])];
        }

        $expiresAt = now()->addMinutes(self::TTL_MINUTES);
        $pairing = ['code' => $this->randomCode(), 'expires_at' => $expiresAt];

        Cache::put($this->cacheKey($party), $pairing, $expiresAt);

        return $pairing;
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
