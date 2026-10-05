<?php

namespace App\Services\Ads;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Fetches and caches the public keys AdMob signs SSV callbacks with.
 *
 * @see https://developers.google.com/admob/android/ssv — confirm this URL and
 *      the response shape against AdMob's live docs before relying on it;
 *      Google documents it as the current source but has moved it before.
 */
class GoogleAdMobKeyProvider
{
    private const CACHE_KEY = 'admob:ssv-verifier-keys';

    /**
     * The PEM public key for a given key_id, or null if unknown — either a
     * genuinely invalid key_id, or one rotated in since the cached fetch
     * (the caller is expected to refresh() and retry once before concluding
     * the latter isn't the case).
     */
    public function pemFor(int $keyId): ?string
    {
        return $this->keys()[$keyId] ?? null;
    }

    /**
     * Forces the next pemFor() lookup to re-fetch rather than use the cache.
     */
    public function refresh(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<int, string> key_id => PEM public key
     */
    private function keys(): array
    {
        $url = config('services.admob.ssv_keys_url');

        if (! $url) {
            return [];
        }

        $ttl = (int) config('services.admob.ssv_keys_cache_ttl', 43200);

        return Cache::remember(self::CACHE_KEY, $ttl, function () use ($url) {
            try {
                $response = Http::acceptJson()->get($url);
            } catch (ConnectionException) {
                return [];
            }

            if ($response->failed()) {
                return [];
            }

            return collect($response->json('keys', []))
                ->mapWithKeys(fn (array $key) => [(int) $key['keyId'] => $key['pem']])
                ->all();
        });
    }
}
