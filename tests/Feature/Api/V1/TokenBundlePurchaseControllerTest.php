<?php

use App\Models\PaymentMethod;
use App\Models\TokenBundle;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function purchaseEndpoint(TokenBundle $bundle): string
{
    return "/api/v1/token-bundles/{$bundle->id}/purchase";
}

it('rejects purchase requests with no bearer token', function () {
    $bundle = TokenBundle::factory()->create();

    $this->postJson(purchaseEndpoint($bundle))->assertStatus(401);
});

it('requires an idempotency key header', function () {
    $token = $this->clerkToken(['sub' => 'user_purchase_no_key']);
    $bundle = TokenBundle::factory()->create(['tokens' => 500]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(purchaseEndpoint($bundle))
        ->assertStatus(422)
        ->assertJsonValidationErrors('idempotency_key');
});

it('purchases a token bundle and credits the wallet', function () {
    $token = $this->clerkToken(['sub' => 'user_purchase_success']);
    $bundle = TokenBundle::factory()->create(['name' => 'Starter Pack', 'tokens' => 500]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_abc123')
        ->postJson(purchaseEndpoint($bundle))
        ->assertStatus(201);

    $response->assertJsonPath('data.amount', 500);
    $response->assertJsonPath('data.balance_after', 500);

    $user = User::where('clerk_user_id', 'user_purchase_success')->firstOrFail();
    $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

    expect($wallet->balance)->toBe(500);
    expect(WalletTransaction::where('wallet_id', $wallet->id)->where('idempotency_key', 'purchase_abc123')->exists())->toBeTrue();
});

it('does not double-credit when the same idempotency key is retried', function () {
    $token = $this->clerkToken(['sub' => 'user_purchase_retry']);
    $bundle = TokenBundle::factory()->create(['tokens' => 250]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_retry_key')
        ->postJson(purchaseEndpoint($bundle))
        ->assertStatus(201);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_retry_key')
        ->postJson(purchaseEndpoint($bundle))
        ->assertStatus(201);

    $user = User::where('clerk_user_id', 'user_purchase_retry')->firstOrFail();
    $wallet = Wallet::where('user_id', $user->id)->firstOrFail();

    expect($wallet->balance)->toBe(250);
    expect(WalletTransaction::where('wallet_id', $wallet->id)->count())->toBe(1);
});

it('returns 404 when purchasing an inactive token bundle', function () {
    $token = $this->clerkToken(['sub' => 'user_purchase_inactive']);
    $bundle = TokenBundle::factory()->create(['is_active' => false]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_inactive_key')
        ->postJson(purchaseEndpoint($bundle))
        ->assertStatus(404);

    $user = User::where('clerk_user_id', 'user_purchase_inactive')->firstOrFail();
    expect(Wallet::where('user_id', $user->id)->exists())->toBeFalse();
});

it('returns 404 when purchasing an unknown token bundle', function () {
    $token = $this->clerkToken(['sub' => 'user_purchase_unknown']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_unknown_key')
        ->postJson('/api/v1/token-bundles/999999/purchase')
        ->assertStatus(404);
});

it('rejects a payment_method_id that belongs to another user', function () {
    $token = $this->clerkToken(['sub' => 'user_purchase_other_method']);
    $bundle = TokenBundle::factory()->create();
    $otherUsersMethod = PaymentMethod::factory()->create();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_other_method_key')
        ->postJson(purchaseEndpoint($bundle), ['payment_method_id' => $otherUsersMethod->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors('payment_method_id');
});

it('purchases via a real Paystack charge by reference, credits the wallet, and saves the card', function () {
    config(['services.paystack.secret_key' => 'sk_test_fake']);

    $token = $this->clerkToken(['sub' => 'user_purchase_paystack_reference']);
    $bundle = TokenBundle::factory()->create(['tokens' => 500, 'price' => 9.99, 'currency' => 'USD']);

    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'data' => [
                'status' => 'success',
                'amount' => 999,
                'currency' => 'USD',
                'authorization' => [
                    'authorization_code' => 'AUTH_e2e_new',
                    'reusable' => true,
                    'last4' => '4242',
                ],
            ],
        ]),
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_paystack_reference_key')
        ->postJson(purchaseEndpoint($bundle), ['payment_reference' => 'ref_e2e_123'])
        ->assertStatus(201)
        ->assertJsonPath('data.amount', 500);

    $user = User::where('clerk_user_id', 'user_purchase_paystack_reference')->firstOrFail();
    expect(Wallet::where('user_id', $user->id)->firstOrFail()->balance)->toBe(500);
    expect(PaymentMethod::where('user_id', $user->id)->where('authorization_code', 'AUTH_e2e_new')->exists())->toBeTrue();
});

it('purchases via a saved payment method', function () {
    config(['services.paystack.secret_key' => 'sk_test_fake']);

    $token = $this->clerkToken(['sub' => 'user_purchase_paystack_saved']);
    $bundle = TokenBundle::factory()->create(['tokens' => 200, 'price' => 4.99, 'currency' => 'USD']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'user_purchase_paystack_saved')->firstOrFail();
    $method = PaymentMethod::factory()->create(['user_id' => $user->id]);

    Http::fake([
        'https://api.paystack.co/transaction/charge_authorization' => Http::response([
            'data' => ['status' => 'success', 'amount' => 499, 'currency' => 'USD'],
        ]),
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_paystack_saved_key')
        ->postJson(purchaseEndpoint($bundle), ['payment_method_id' => $method->id])
        ->assertStatus(201);

    expect(Wallet::where('user_id', $user->id)->firstOrFail()->balance)->toBe(200);
});

it('declines and does not credit the wallet when Paystack verification fails', function () {
    config(['services.paystack.secret_key' => 'sk_test_fake']);

    $token = $this->clerkToken(['sub' => 'user_purchase_paystack_declined']);
    $bundle = TokenBundle::factory()->create(['tokens' => 500, 'price' => 9.99, 'currency' => 'USD']);

    Http::fake([
        'https://api.paystack.co/transaction/verify/*' => Http::response([
            'data' => ['status' => 'failed', 'amount' => 999, 'currency' => 'USD'],
        ]),
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_paystack_declined_key')
        ->postJson(purchaseEndpoint($bundle), ['payment_reference' => 'ref_declined'])
        ->assertStatus(402);

    $user = User::where('clerk_user_id', 'user_purchase_paystack_declined')->firstOrFail();
    expect(Wallet::where('user_id', $user->id)->first()?->balance ?? 0)->toBe(0);
    expect(WalletTransaction::where('idempotency_key', 'purchase_paystack_declined_key')->exists())->toBeFalse();
});
