<?php

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakesClerk;

const API_V1_PAYMENT_METHODS_ENDPOINT = '/api/v1/wallet/payment-methods';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

// authAs() is a shared helper declared in tests/Pest.php.

it('rejects requests with no bearer token', function () {
    $this->getJson(API_V1_PAYMENT_METHODS_ENDPOINT)->assertStatus(401);
});

it('lists only the authenticated users own payment methods, default first', function () {
    $viewer = authAs('user_pm_list');

    $other = User::factory()->create();
    PaymentMethod::factory()->create(['user_id' => $other->id]);

    PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => false, 'last4' => '1111']);
    PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => true, 'last4' => '2222']);

    $response = $this->getJson(API_V1_PAYMENT_METHODS_ENDPOINT)->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    $response->assertJsonPath('data.0.last4', '2222');
    $response->assertJsonPath('data.0.is_default', true);

    // The sensitive authorization code must never be serialized.
    expect($response->json('data.0'))->not->toHaveKey('authorization_code');
});

it('lets a user set one of their own payment methods as default', function () {
    $viewer = authAs('user_pm_set_default');

    $current = PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => true]);
    $target = PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => false]);

    $this->patchJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$target->id}/default")
        ->assertOk()
        ->assertJsonPath('data.is_default', true);

    expect($current->refresh()->is_default)->toBeFalse();
    expect($target->refresh()->is_default)->toBeTrue();
});

it('lets a user delete their own payment method', function () {
    $viewer = authAs('user_pm_delete');

    $method = PaymentMethod::factory()->create(['user_id' => $viewer->id]);

    $this->deleteJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$method->id}")->assertOk();

    expect(PaymentMethod::find($method->id))->toBeNull();
});

it('deactivates the Paystack authorization when a payment method is deleted', function () {
    Http::fake(['https://api.paystack.co/customer/deactivate_authorization' => Http::response(['status' => true], 200)]);
    config(['services.paystack.secret_key' => 'sk_test_pm_delete']);
    $viewer = authAs('user_pm_delete_deactivates');

    $method = PaymentMethod::factory()->create(['user_id' => $viewer->id, 'authorization_code' => 'AUTH_to_deactivate']);

    $this->deleteJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$method->id}")->assertOk();

    Http::assertSent(fn (HttpRequest $request) => $request->url() === 'https://api.paystack.co/customer/deactivate_authorization'
        && $request['authorization_code'] === 'AUTH_to_deactivate');
    expect(PaymentMethod::find($method->id))->toBeNull();
});

it('still deletes the payment method locally even if Paystack deactivation fails', function () {
    Http::fake(['https://api.paystack.co/customer/deactivate_authorization' => Http::response(['status' => false], 500)]);
    config(['services.paystack.secret_key' => 'sk_test_pm_delete_fails']);
    $viewer = authAs('user_pm_delete_paystack_down');

    $method = PaymentMethod::factory()->create(['user_id' => $viewer->id]);

    $this->deleteJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$method->id}")->assertOk();

    expect(PaymentMethod::find($method->id))->toBeNull();
});

it('promotes the next most recent method to default when the default is deleted', function () {
    $viewer = authAs('user_pm_promote');

    $older = PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => false, 'created_at' => now()->subHour()]);
    $default = PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => true]);

    $this->deleteJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$default->id}")->assertOk();

    expect($older->refresh()->is_default)->toBeTrue();
});

it('rejects setting default or deleting a payment method that belongs to another user', function () {
    authAs('user_pm_forbidden');

    $other = User::factory()->create();
    $method = PaymentMethod::factory()->create(['user_id' => $other->id]);

    $this->patchJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$method->id}/default")->assertStatus(403);
    $this->deleteJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$method->id}")->assertStatus(403);

    expect(PaymentMethod::find($method->id))->not->toBeNull();
});
