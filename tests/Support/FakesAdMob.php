<?php

namespace Tests\Support;

use App\Services\Ads\GoogleAdMobKeyProvider;
use Illuminate\Support\Facades\Http;

trait FakesAdMob
{
    protected string $adMobSsvKeysUrl = 'https://test.gstatic.dev/admob/reward/verifier-keys.json';

    protected int $adMobTestKeyId = 1;

    /**
     * @var \OpenSSLAsymmetricKey
     */
    private $adMobPrivateKey;

    protected function fakeAdMobSsvKeys(): void
    {
        config(['services.admob.ssv_keys_url' => $this->adMobSsvKeysUrl]);

        // The array cache driver isn't reset between tests the way the
        // database is — without this, an earlier test's cached keyset
        // (possibly from a different fake keypair) would leak in here.
        app(GoogleAdMobKeyProvider::class)->refresh();

        // The "private_key_bits" here is a no-op for an EC key (the curve
        // alone determines key size) but PHP's openssl binding validates it
        // against a floor regardless of key type — omitting it fails with
        // "Private key length must be at least 384 bits" even for EC.
        $keyPair = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
            'private_key_bits' => 384,
        ]);

        $this->adMobPrivateKey = $keyPair;
        $pem = openssl_pkey_get_details($keyPair)['key'];

        Http::fake([
            $this->adMobSsvKeysUrl => Http::response([
                'keys' => [
                    ['keyId' => $this->adMobTestKeyId, 'pem' => $pem, 'base64' => base64_encode($pem)],
                ],
            ], 200),
        ]);
    }

    /**
     * Builds a full, real, SSV-callback-shaped query string — content signed
     * with the fake key exactly as AdMob's own docs specify (every param but
     * signature/key_id, in the given order, verbatim), then signature and
     * key_id appended afterward.
     *
     * The signature is base64url-encoded (-/_ , no padding), matching what
     * Google actually sends — not plain base64. Encoding it as plain base64
     * here would never catch AdMobSsvVerifier decoding it as plain base64
     * in production, since standard base64_encode() never emits a -/_
     * character for strtr() to even have something to fix.
     *
     * @param  array<string, string>  $params
     */
    protected function signedAdMobQuery(array $params, ?int $keyId = null): string
    {
        $content = http_build_query($params);

        openssl_sign($content, $signatureBinary, $this->adMobPrivateKey, OPENSSL_ALGO_SHA256);

        $signature = rtrim(strtr(base64_encode($signatureBinary), '+/', '-_'), '=');

        return $content.'&signature='.$signature.'&key_id='.($keyId ?? $this->adMobTestKeyId);
    }
}
