<?php

namespace App\Services\Clerk;

use App\Exceptions\Api\InvalidClerkTokenException;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class ClerkJwtVerifier
{
    /**
     * Verify a Clerk-issued JWT and return its decoded claims.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidClerkTokenException
     */
    public function verify(string $token): array
    {
        $keys = $this->keySet();

        try {
            $claims = (array) JWT::decode($token, $keys);
        } catch (Throwable $e) {
            throw new InvalidClerkTokenException('Invalid or expired authentication token.');
        }

        $issuer = config('services.clerk.issuer');

        if ($issuer && ($claims['iss'] ?? null) !== $issuer) {
            throw new InvalidClerkTokenException('Token was issued by an untrusted issuer.');
        }

        if (empty($claims['sub'])) {
            throw new InvalidClerkTokenException('Token is missing a subject claim.');
        }

        return $claims;
    }

    /**
     * @return array<string, Key>
     */
    protected function keySet(): array
    {
        $jwksUrl = config('services.clerk.jwks_url');

        if (! $jwksUrl) {
            throw new InvalidClerkTokenException('Clerk JWKS URL is not configured.');
        }

        $ttl = (int) config('services.clerk.jwks_cache_ttl', 3600);

        $jwks = Cache::remember('clerk:jwks', $ttl, function () use ($jwksUrl) {
            $response = Http::acceptJson()->get($jwksUrl);

            if ($response->failed()) {
                throw new InvalidClerkTokenException('Unable to fetch Clerk signing keys.');
            }

            return $response->json();
        });

        return JWK::parseKeySet($jwks);
    }
}
