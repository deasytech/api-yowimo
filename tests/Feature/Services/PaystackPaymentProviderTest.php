<?php

use App\Models\PaymentMethod;
use App\Models\TokenBundle;
use App\Models\User;
use App\Services\Purchase\PaystackPaymentProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config(['services.paystack.secret_key' => 'sk_test_fake']);
});

function paystackVerifyUrl(string $reference): string
{
    return "https://api.paystack.co/transaction/verify/{$reference}";
}

const PAYSTACK_CHARGE_AUTHORIZATION_URL = 'https://api.paystack.co/transaction/charge_authorization';
const PAYSTACK_CONNECTION_ERROR_MESSAGE = 'Connection timed out';

it('charges a saved payment method and approves when the amount and currency match', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 9.99, 'currency' => 'USD', 'tokens' => 500]);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'data' => ['status' => 'success', 'amount' => 999, 'currency' => 'USD'],
        ]),
    ]);

    $approved = app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method);

    expect($approved)->toBeTrue();
    Http::assertSent(fn ($request) => $request->url() === PAYSTACK_CHARGE_AUTHORIZATION_URL
        && $request['authorization_code'] === $method->authorization_code
        && $request['amount'] === 999);
});

it('charges a Nigerian buyer the default (Naira) price, ignoring any USD price on the bundle', function () {
    $user = User::factory()->create(['country_code' => 'NG']);
    $bundle = TokenBundle::factory()->create(['price' => 3000, 'currency' => 'NGN', 'price_usd' => 9.99, 'tokens' => 500]);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'data' => ['status' => 'success', 'amount' => 300000, 'currency' => 'NGN'],
        ]),
    ]);

    $approved = app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method);

    expect($approved)->toBeTrue();
    Http::assertSent(fn ($request) => $request->url() === PAYSTACK_CHARGE_AUTHORIZATION_URL
        && $request['amount'] === 300000
        && $request['currency'] === 'NGN');
});

it('charges a confirmed non-Nigerian buyer in USD using the bundle USD price', function () {
    $user = User::factory()->create(['country_code' => 'US']);
    $bundle = TokenBundle::factory()->create(['price' => 3000, 'currency' => 'NGN', 'price_usd' => 9.99, 'tokens' => 500]);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'data' => ['status' => 'success', 'amount' => 999, 'currency' => 'USD'],
        ]),
    ]);

    $approved = app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method);

    expect($approved)->toBeTrue();
    Http::assertSent(fn ($request) => $request->url() === PAYSTACK_CHARGE_AUTHORIZATION_URL
        && $request['amount'] === 999
        && $request['currency'] === 'USD');
});

it('declines a saved-method charge if Paystack is charged in the wrong currency for this buyer', function () {
    // Guards against a stale/cached price resolving to the wrong
    // amount+currency for this buyer — the charged currency must match
    // what priceFor() resolved, not whatever the bundle's default happens
    // to be.
    $user = User::factory()->create(['country_code' => 'US']);
    $bundle = TokenBundle::factory()->create(['price' => 3000, 'currency' => 'NGN', 'price_usd' => 9.99]);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'data' => ['status' => 'success', 'amount' => 300000, 'currency' => 'NGN'],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method))->toBeFalse();
});

it('declines a saved-method charge when the charged amount does not match the bundle price', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 9.99, 'currency' => 'USD']);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'data' => ['status' => 'success', 'amount' => 100, 'currency' => 'USD'],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method))->toBeFalse();
});

it('falls back to the users default saved method when neither is given', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 5, 'currency' => 'USD']);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id, 'is_default' => true]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'data' => ['status' => 'success', 'amount' => 500, 'currency' => 'USD'],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle))->toBeTrue();

    Http::assertSent(fn ($request) => ($request['authorization_code'] ?? null) === $method->authorization_code);
});

it('derives the same Paystack reference for repeated attempts of the same idempotency key', function () {
    // So a retried saved-method charge (e.g. after a dropped connection)
    // resolves to the original transaction at Paystack instead of
    // charging the card a second time.
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 9.99, 'currency' => 'USD']);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'data' => ['status' => 'success', 'amount' => 999, 'currency' => 'USD'],
        ]),
    ]);

    app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method, idempotencyKey: 'purchase_retry_key');
    app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method, idempotencyKey: 'purchase_retry_key');

    $references = collect(Http::recorded())
        ->map(fn ($pair) => $pair[0]['reference'])
        ->unique();

    expect($references)->toHaveCount(1);
});

it('resolves a saved-method charge by verifying instead of assuming decline when the connection drops', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 9.99, 'currency' => 'USD']);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => fn () => throw new ConnectionException(PAYSTACK_CONNECTION_ERROR_MESSAGE),
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'data' => ['status' => 'success', 'amount' => 999, 'currency' => 'USD'],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method, idempotencyKey: 'purchase_dropped_key'))->toBeTrue();
});

it('resolves a saved-method charge by verifying when the synchronous response does not confirm success', function () {
    // A charge_authorization call can complete without an outright
    // "success" (e.g. a non-final status) — verifying by reference
    // resolves whether it actually went through instead of assuming
    // decline from a response that isn't the final word.
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 9.99, 'currency' => 'USD']);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'data' => ['status' => 'pending', 'amount' => 999, 'currency' => 'USD'],
        ]),
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'data' => ['status' => 'success', 'amount' => 999, 'currency' => 'USD'],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method, idempotencyKey: 'purchase_pending_key'))->toBeTrue();
});

it('declines a saved-method charge when both the synchronous response and the fallback verify fail', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 9.99, 'currency' => 'USD']);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'data' => ['status' => 'failed', 'amount' => 999, 'currency' => 'USD'],
        ]),
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'data' => ['status' => 'failed', 'amount' => 999, 'currency' => 'USD'],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method, idempotencyKey: 'purchase_declined_key'))->toBeFalse();
});

it('declines a saved-method charge when both the charge and the fallback verify cannot connect', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 9.99, 'currency' => 'USD']);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake(fn () => throw new ConnectionException(PAYSTACK_CONNECTION_ERROR_MESSAGE));

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method, idempotencyKey: 'purchase_unreachable_key'))->toBeFalse();
});

it('declines when no payment method or reference is available', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create();

    Http::fake();

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle))->toBeFalse();
    Http::assertNothingSent();
});

it('verifies a reference, approves, and saves a reusable authorization as a new payment method', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 15, 'currency' => 'USD', 'tokens' => 1000]);

    Http::fake([
        paystackVerifyUrl('ref_abc123') => Http::response([
            'data' => [
                'status' => 'success',
                'amount' => 1500,
                'currency' => 'USD',
                'customer' => ['email' => $user->email],
                'authorization' => [
                    'authorization_code' => 'AUTH_new123',
                    'reusable' => true,
                    'card_type' => 'visa',
                    'last4' => '4242',
                    'exp_month' => '12',
                    'exp_year' => '2030',
                    'bank' => 'Test Bank',
                ],
            ],
        ]),
    ]);

    $approved = app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentReference: 'ref_abc123');

    expect($approved)->toBeTrue();

    $saved = PaymentMethod::where('user_id', $user->id)->where('authorization_code', 'AUTH_new123')->first();
    expect($saved)->not->toBeNull();
    expect($saved->last4)->toBe('4242');
    expect($saved->is_default)->toBeTrue();
});

it('does not save a non-reusable authorization from a reference charge', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 15, 'currency' => 'USD']);

    Http::fake([
        paystackVerifyUrl('ref_onetime') => Http::response([
            'data' => [
                'status' => 'success',
                'amount' => 1500,
                'currency' => 'USD',
                'customer' => ['email' => $user->email],
                'authorization' => ['authorization_code' => 'AUTH_onetime', 'reusable' => false],
            ],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentReference: 'ref_onetime'))->toBeTrue();
    expect(PaymentMethod::where('authorization_code', 'AUTH_onetime')->exists())->toBeFalse();
});

it('declines a reference charge when verification reports a failed status', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 15, 'currency' => 'USD']);

    Http::fake([
        paystackVerifyUrl('ref_failed') => Http::response([
            'data' => ['status' => 'failed', 'amount' => 1500, 'currency' => 'USD', 'customer' => ['email' => $user->email]],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentReference: 'ref_failed'))->toBeFalse();
});

it('declines a reference charge whose customer email does not belong to the caller', function () {
    // Otherwise-valid, correctly-priced transaction — but for someone
    // else's Paystack customer. Submitting a real, successful reference
    // that isn't actually yours must not credit your wallet.
    $user = User::factory()->create(['email' => 'buyer@example.com']);
    $bundle = TokenBundle::factory()->create(['price' => 15, 'currency' => 'USD']);

    Http::fake([
        paystackVerifyUrl('ref_not_mine') => Http::response([
            'data' => ['status' => 'success', 'amount' => 1500, 'currency' => 'USD', 'customer' => ['email' => 'someone-else@example.com']],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentReference: 'ref_not_mine'))->toBeFalse();
});

it('declines a reference charge when the caller has no email on file', function () {
    $user = User::factory()->create(['email' => null]);
    $bundle = TokenBundle::factory()->create(['price' => 15, 'currency' => 'USD']);

    Http::fake([
        paystackVerifyUrl('ref_no_email') => Http::response([
            'data' => ['status' => 'success', 'amount' => 1500, 'currency' => 'USD', 'customer' => ['email' => 'someone@example.com']],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentReference: 'ref_no_email'))->toBeFalse();
});

it('declines a reference charge instead of reassigning an authorization already saved to another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 15, 'currency' => 'USD']);
    $stolenAuth = PaymentMethod::factory()->create(['user_id' => $owner->id, 'authorization_code' => 'AUTH_owned_by_owner']);

    Http::fake([
        paystackVerifyUrl('ref_conflict') => Http::response([
            'data' => [
                'status' => 'success',
                'amount' => 1500,
                'currency' => 'USD',
                'customer' => ['email' => $intruder->email],
                'authorization' => ['authorization_code' => 'AUTH_owned_by_owner', 'reusable' => true],
            ],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($intruder, $bundle, paymentReference: 'ref_conflict'))->toBeFalse();
    expect($stolenAuth->refresh()->user_id)->toBe($owner->id);
});

it('declines cleanly, without throwing, when Paystack 400s a not-found reference', function () {
    // Paystack's real shape for an invalid/expired/not-found reference: a
    // non-2xx status with no `data` key at all — different from a genuine
    // decline (200, `data.status: "failed"`). Confirmed against the live
    // API in test mode; without PaystackClient handling this, Http::throw()
    // would surface it as an uncaught 500 instead of a clean 402.
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 15, 'currency' => 'USD']);

    Http::fake([
        paystackVerifyUrl('ref_not_found') => Http::response([
            'status' => false,
            'message' => 'Transaction reference not found.',
        ], 400),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentReference: 'ref_not_found'))->toBeFalse();
});

it('declines cleanly, without throwing, when Paystack 400s a malformed authorization code', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 15, 'currency' => 'USD']);
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        PAYSTACK_CHARGE_AUTHORIZATION_URL => Http::response([
            'status' => false,
            'message' => '"authorization_code" fails to match the required pattern',
        ], 400),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentMethod: $method))->toBeFalse();
});

it('declines cleanly, without throwing, when Paystack is unreachable', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['price' => 15, 'currency' => 'USD']);

    Http::fake(fn () => throw new ConnectionException(PAYSTACK_CONNECTION_ERROR_MESSAGE));

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentReference: 'ref_unreachable'))->toBeFalse();
});
