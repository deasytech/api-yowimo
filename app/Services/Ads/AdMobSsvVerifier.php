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

    public function verify(Request $request): bool
    {
        $keyId = $request->query('key_id');
        $signature = $request->query('signature');
        $content = $this->contentToVerify($request);

        if ($keyId === null || $signature === null || $content === null) {
            return false;
        }

        $signatureBinary = base64_decode($signature, true);

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
     * Must be the raw, untouched query string exactly as Google sent it —
     * $request->getQueryString() is not safe to use here: Symfony's version
     * reorders parameters alphabetically and re-encodes them, which breaks
     * verification the moment Google's actual order/encoding differs from
     * that normalized form.
     */
    private function contentToVerify(Request $request): ?string
    {
        $queryString = $request->server->get('QUERY_STRING', '');
        $signaturePos = strpos($queryString, 'signature=');

        if ($signaturePos === false || $signaturePos === 0) {
            return null;
        }

        return substr($queryString, 0, $signaturePos - 1);
    }
}
