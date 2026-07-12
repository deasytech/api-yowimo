<?php

use App\Exceptions\Api\InvalidClerkTokenException;
use App\Services\Clerk\ClerkJwtVerifier;
use Firebase\JWT\JWT;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('verifies a validly signed token and returns its claims', function () {
    $token = $this->clerkToken(['sub' => 'user_1', 'email' => 'a@b.com']);

    $claims = app(ClerkJwtVerifier::class)->verify($token);

    expect($claims['sub'])->toBe('user_1');
    expect($claims['email'])->toBe('a@b.com');
});

it('rejects a token signed by an untrusted key', function () {
    $keyPair = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    openssl_pkey_export($keyPair, $rogueKey);

    $token = JWT::encode([
        'sub' => 'user_1',
        'iss' => $this->clerkIssuer,
        'iat' => time(),
        'exp' => time() + 3600,
    ], $rogueKey, 'RS256', 'test-key');

    app(ClerkJwtVerifier::class)->verify($token);
})->throws(InvalidClerkTokenException::class);

it('rejects an expired token', function () {
    $token = $this->clerkToken(['exp' => time() - 10]);

    app(ClerkJwtVerifier::class)->verify($token);
})->throws(InvalidClerkTokenException::class);

it('rejects a token from an untrusted issuer', function () {
    $token = $this->clerkToken(['iss' => 'https://someone-elses-clerk.dev']);

    app(ClerkJwtVerifier::class)->verify($token);
})->throws(InvalidClerkTokenException::class);

it('rejects a token that is not yet valid', function () {
    $token = $this->clerkToken(['nbf' => time() + 3600]);

    app(ClerkJwtVerifier::class)->verify($token);
})->throws(InvalidClerkTokenException::class);
