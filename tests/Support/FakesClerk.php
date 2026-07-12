<?php

namespace Tests\Support;

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Http;

trait FakesClerk
{
    protected string $clerkIssuer = 'https://test.clerk.accounts.dev';

    protected string $clerkJwksUrl = 'https://test.clerk.accounts.dev/.well-known/jwks.json';

    /**
     * @var resource|\OpenSSLAsymmetricKey|null
     */
    private $clerkPrivateKey;

    protected function fakeClerk(): void
    {
        config([
            'services.clerk.issuer' => $this->clerkIssuer,
            'services.clerk.jwks_url' => $this->clerkJwksUrl,
        ]);

        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($keyPair, $privateKeyPem);
        $details = openssl_pkey_get_details($keyPair);

        $this->clerkPrivateKey = $privateKeyPem;

        $jwk = [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => 'RS256',
            'kid' => 'test-key',
            'n' => $this->base64UrlEncode($details['rsa']['n']),
            'e' => $this->base64UrlEncode($details['rsa']['e']),
        ];

        Http::fake([
            $this->clerkJwksUrl => Http::response(['keys' => [$jwk]], 200),
        ]);
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    protected function clerkToken(array $claims = []): string
    {
        $payload = array_merge([
            'sub' => 'user_'.str()->random(20),
            'iss' => $this->clerkIssuer,
            'iat' => time(),
            'exp' => time() + 3600,
        ], $claims);

        return JWT::encode($payload, $this->clerkPrivateKey, 'RS256', 'test-key');
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
