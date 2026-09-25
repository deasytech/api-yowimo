<?php

use App\Models\PaymentMethod;
use App\Models\User;
use Tests\Support\FakesClerk;

const API_V1_PAYMENT_METHODS_ENDPOINT = '/api/v1/wallet/payment-methods';
const API_V1_PM_ME_ENDPOINT = '/api/v1/users/me';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects requests with no bearer token', function () {
    $this->getJson(API_V1_PAYMENT_METHODS_ENDPOINT)->assertStatus(401);
});

it('lists only the authenticated users own payment methods, default first', function () {
    $token = $this->clerkToken(['sub' => 'user_pm_list']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_PM_ME_ENDPOINT)->assertOk();
    $viewer = User::where('clerk_user_id', 'user_pm_list')->firstOrFail();

    $other = User::factory()->create();
    PaymentMethod::factory()->create(['user_id' => $other->id]);

    PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => false, 'last4' => '1111']);
    PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => true, 'last4' => '2222']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PAYMENT_METHODS_ENDPOINT)
        ->assertOk();

    expect($response->json('data'))->toHaveCount(2);
    $response->assertJsonPath('data.0.last4', '2222');
    $response->assertJsonPath('data.0.is_default', true);

    // The sensitive authorization code must never be serialized.
    expect($response->json('data.0'))->not->toHaveKey('authorization_code');
});

it('lets a user set one of their own payment methods as default', function () {
    $token = $this->clerkToken(['sub' => 'user_pm_set_default']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_PM_ME_ENDPOINT)->assertOk();
    $viewer = User::where('clerk_user_id', 'user_pm_set_default')->firstOrFail();

    $current = PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => true]);
    $target = PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => false]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$target->id}/default")
        ->assertOk()
        ->assertJsonPath('data.is_default', true);

    expect($current->refresh()->is_default)->toBeFalse();
    expect($target->refresh()->is_default)->toBeTrue();
});

it('lets a user delete their own payment method', function () {
    $token = $this->clerkToken(['sub' => 'user_pm_delete']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_PM_ME_ENDPOINT)->assertOk();
    $viewer = User::where('clerk_user_id', 'user_pm_delete')->firstOrFail();

    $method = PaymentMethod::factory()->create(['user_id' => $viewer->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$method->id}")
        ->assertOk();

    expect(PaymentMethod::find($method->id))->toBeNull();
});

it('promotes the next most recent method to default when the default is deleted', function () {
    $token = $this->clerkToken(['sub' => 'user_pm_promote']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_PM_ME_ENDPOINT)->assertOk();
    $viewer = User::where('clerk_user_id', 'user_pm_promote')->firstOrFail();

    $older = PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => false, 'created_at' => now()->subHour()]);
    $default = PaymentMethod::factory()->create(['user_id' => $viewer->id, 'is_default' => true]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$default->id}")
        ->assertOk();

    expect($older->refresh()->is_default)->toBeTrue();
});

it('rejects setting default or deleting a payment method that belongs to another user', function () {
    $token = $this->clerkToken(['sub' => 'user_pm_forbidden']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_PM_ME_ENDPOINT)->assertOk();

    $other = User::factory()->create();
    $method = PaymentMethod::factory()->create(['user_id' => $other->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$method->id}/default")
        ->assertStatus(403);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(API_V1_PAYMENT_METHODS_ENDPOINT."/{$method->id}")
        ->assertStatus(403);

    expect(PaymentMethod::find($method->id))->not->toBeNull();
});
