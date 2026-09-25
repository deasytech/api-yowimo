<?php

use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Purchase\PaymentMethodService;

it('never includes authorization_code when the model is serialized directly', function () {
    // Defense in depth: PaymentMethodResource already omits this explicitly,
    // but the model itself should refuse to leak it too, in case some other
    // code path ever serializes it directly.
    $method = PaymentMethod::factory()->create(['authorization_code' => 'AUTH_should_stay_hidden']);

    expect($method->toArray())->not->toHaveKey('authorization_code');
    expect($method->authorization_code)->toBe('AUTH_should_stay_hidden');
});

it('creates a new payment method and makes it the default when it is the first one', function () {
    $user = User::factory()->create();

    $method = app(PaymentMethodService::class)->saveFromAuthorization($user, 'paystack', [
        'authorization_code' => 'AUTH_first',
        'card_type' => 'visa',
        'last4' => '4242',
    ]);

    expect($method)->not->toBeNull();
    expect($method->user_id)->toBe($user->id);
    expect($method->is_default)->toBeTrue();
});

it('refreshes an existing payment methods metadata for the same owner instead of duplicating it', function () {
    $user = User::factory()->create();
    $existing = PaymentMethod::factory()->create([
        'user_id' => $user->id,
        'authorization_code' => 'AUTH_refresh',
        'last4' => '0000',
    ]);

    $method = app(PaymentMethodService::class)->saveFromAuthorization($user, 'paystack', [
        'authorization_code' => 'AUTH_refresh',
        'last4' => '9999',
    ]);

    expect($method->id)->toBe($existing->id);
    expect($method->last4)->toBe('9999');
    expect(PaymentMethod::where('authorization_code', 'AUTH_refresh')->count())->toBe(1);
});

it('refuses to reassign an authorization already saved to a different user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    PaymentMethod::factory()->create(['user_id' => $owner->id, 'authorization_code' => 'AUTH_owned']);

    $result = app(PaymentMethodService::class)->saveFromAuthorization($intruder, 'paystack', [
        'authorization_code' => 'AUTH_owned',
        'last4' => '1111',
    ]);

    expect($result)->toBeNull();
    $unchanged = PaymentMethod::where('authorization_code', 'AUTH_owned')->firstOrFail();
    expect($unchanged->user_id)->toBe($owner->id);
    expect($unchanged->last4)->not->toBe('1111');
});
