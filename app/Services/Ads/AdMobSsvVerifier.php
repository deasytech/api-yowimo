<?php

namespace App\Services\Ads;

use Illuminate\Http\Request;

/**
 * Verifies an AdMob rewarded-ad SSV callback's signature.
 *
 * @see https://developers.google.com/admob/android/ssv — re-confirm this
 *      algorithm against the live docs before relying on it; it's the piece
 *      of this integration most likely to have changed since. As documented
 *      there: the callback's query parameters, *in the exact order Google
 *      sent them*, up to (not including) the "&" before "signature=", form
 *      the content to verify. "signature" and the trailing "key_id" are
 *      never part of that content. The signature itself is a DER-encoded
 *      ECDSA-over-P-256 signature of the SHA-256 digest of that content,
 *      base64-encoded, matching Google's reference Java implementation
 *      (Tink's EcdsaVerifyJce with EcdsaEncoding.DER).
 */
class AdMobSsvVerifier
{
    public function __construct(private readonly GoogleAdMobKeyProvider $keys) {}

    /**
     * Verifies the callback's signature and, only when it's valid, returns
     * the exact parameters that were actually signed. Deliberately never
     * $request->query(): if a duplicate parameter were appended after
     * key_id, $request->query() could resolve it to that appended value
     * instead of the one Google actually signed, while the signature itself
     * would still check out (parseSignedQuery() only verifies the untouched
     * prefix). Returning parsed-from-$content params instead closes that
     * off structurally — callers have no way to get params that weren't
     * part of the verified content.
     *
     * @return array<string, mixed>|null
     */
    public function verify(Request $request): ?array
    {
        $parsed = $this->parseSignedQuery($request->server->get('QUERY_STRING', ''));

        if ($parsed === null) {
            return null;
        }

        [$content, $signature, $keyId] = $parsed;

        if (! $this->signatureIsValid($signature, $keyId, $content)) {
            return null;
        }

        parse_str($content, $params);

        return $params;
    }

    private function signatureIsValid(string $signature, string $keyId, string $content): bool
    {
        // Google signs this as base64url (-/_ in place of +//), not standard
        // base64 — decoding it as standard base64 in strict mode silently
        // rejects every real signature, since -/_ aren't in that alphabet.
        $signatureBinary = base64_decode(strtr($signature, '-_', '+/'), true);

        if ($signatureBinary === false) {
            return false;
        }

        if ($this->verifyAgainstKey((int) $keyId, $content, $signatureBinary)) {
            return true;
        }

        // Google rotates these keys with an overlap window; a brand new
        // key_id we haven't cached yet looks identical to a genuinely bad
        // signature from here, so force a refresh and retry once before
        // concluding it's actually invalid.
        $this->keys->refresh();

        return $this->verifyAgainstKey((int) $keyId, $content, $signatureBinary);
    }

    private function verifyAgainstKey(int $keyId, string $content, string $signatureBinary): bool
    {
        $pem = $this->keys->pemFor($keyId);

        if ($pem === null) {
            return false;
        }

        return openssl_verify($content, $signatureBinary, $pem, OPENSSL_ALGO_SHA256) === 1;
    }

    /**
     * Must be built from the raw, untouched query string exactly as Google
     * sent it — $request->getQueryString() is not safe to use here:
     * Symfony's version reorders parameters alphabetically and re-encodes
     * them, which breaks verification the moment Google's actual
     * order/encoding differs from that normalized form.
     *
     * Requires the query string to end in exactly "&signature=...&key_id=..."
     * with nothing after — a callback with its legitimate signed prefix
     * intact but an extra parameter appended after key_id would otherwise
     * still verify (the signed content itself is unchanged), which is
     * exactly what would let $request->query() disagree with $content about
     * a duplicated parameter's value. Rejecting the whole request here is
     * what makes returning params parsed from $content safe.
     *
     * @return array{0: string, 1: string, 2: string}|null [content, signature, key_id]
     */
    private function parseSignedQuery(string $queryString): ?array
    {
        if (! preg_match('/^(.+)&signature=([^&]+)&key_id=([^&]+)$/', $queryString, $matches)) {
            return null;
        }

        return [$matches[1], $matches[2], $matches[3]];
    }
}
