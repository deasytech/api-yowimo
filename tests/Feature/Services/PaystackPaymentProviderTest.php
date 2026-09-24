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
            'data' => ['status' => 'failed', 'amount' => 1500, 'currency' => 'USD'],
        ]),
    ]);

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentReference: 'ref_failed'))->toBeFalse();
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

    Http::fake(fn () => throw new ConnectionException('Connection timed out'));

    expect(app(PaystackPaymentProvider::class)->charge($user, $bundle, paymentReference: 'ref_unreachable'))->toBeFalse();
});
