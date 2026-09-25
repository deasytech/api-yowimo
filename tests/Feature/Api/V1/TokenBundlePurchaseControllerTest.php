<?php

use App\Models\TokenBundle;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
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
