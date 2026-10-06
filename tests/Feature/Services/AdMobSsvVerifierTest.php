<?php

use App\Services\Ads\AdMobSsvVerifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakesAdMob;

uses(FakesAdMob::class);

beforeEach(function () {
    $this->fakeAdMobSsvKeys();
});

function adMobRequestFor(string $signedQuery): Request
{
    return Request::create("/api/v1/webhooks/admob-ssv?{$signedQuery}", 'GET');
}

it('verifies a validly signed callback and returns its signed params', function () {
    $query = $this->signedAdMobQuery(['ad_network' => '123', 'transaction_id' => 'txn-1']);

    $result = app(AdMobSsvVerifier::class)->verify(adMobRequestFor($query));

    expect($result)->toBe(['ad_network' => '123', 'transaction_id' => 'txn-1']);
});

it('rejects a callback whose content was tampered with after signing', function () {
    $query = $this->signedAdMobQuery(['ad_network' => '123', 'transaction_id' => 'txn-1']);
    $tampered = str_replace('transaction_id=txn-1', 'transaction_id=txn-EVIL', $query);

    expect(app(AdMobSsvVerifier::class)->verify(adMobRequestFor($tampered)))->toBeNull();
});

it('rejects a callback missing signature or key_id', function () {
    expect(app(AdMobSsvVerifier::class)->verify(adMobRequestFor('ad_network=123&transaction_id=txn-1')))->toBeNull();
});

it('rejects a callback with an extra parameter appended after key_id', function () {
    // The attack this guards against: capture any one legitimately-signed
    // callback, then append a duplicate parameter after key_id. The signed
    // prefix is untouched, so a naive check would still treat the signature
    // as valid — but $request->query() would resolve the duplicated key to
    // this appended value instead of the one actually signed. Must reject
    // outright rather than let that ambiguity through.
    $query = $this->signedAdMobQuery(['ad_network' => '123', 'custom_data' => 'legit-token']);

    expect(app(AdMobSsvVerifier::class)->verify(adMobRequestFor($query.'&custom_data=attacker-chosen')))->toBeNull();
});

it('rejects a callback whose signature does not match any known key_id, even after a refresh retry', function () {
    $query = $this->signedAdMobQuery(['ad_network' => '123'], keyId: 999);

    expect(app(AdMobSsvVerifier::class)->verify(adMobRequestFor($query)))->toBeNull();
});

it('retries once against freshly fetched keys before rejecting a key_id rotated in since the last cache', function () {
    // Simulates Google rotating in a key_id our cache doesn't know about
    // yet: the first fetch (verify()'s initial attempt) returns a stale,
    // empty keyset; only the second fetch (after verify()'s own
    // refresh-and-retry clears the cache) has the real key. A sequence,
    // not two competing Http::fake() registrations for the same URL —
    // which of those "wins" isn't something to depend on.
    $pem = openssl_pkey_get_details($this->adMobPrivateKey)['key'];

    Http::fake([
        $this->adMobSsvKeysUrl => Http::sequence()
            ->push(['keys' => []], 200)
            ->push(['keys' => [['keyId' => $this->adMobTestKeyId, 'pem' => $pem, 'base64' => base64_encode($pem)]]], 200),
    ]);

    $query = $this->signedAdMobQuery(['ad_network' => '123']);

    expect(app(AdMobSsvVerifier::class)->verify(adMobRequestFor($query)))->not->toBeNull();
});
