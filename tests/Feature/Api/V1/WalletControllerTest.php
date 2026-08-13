<?php

use App\Enums\WalletTransactionType;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Policies\WalletPolicy;
use Tests\Support\FakesClerk;

const API_V1_WALLET_ENDPOINT = '/api/v1/wallet';
const API_V1_WALLET_TRANSACTIONS_ENDPOINT = '/api/v1/wallet/transactions';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects wallet requests with no bearer token', function () {
    $this->getJson(API_V1_WALLET_ENDPOINT)->assertStatus(401);
});

it('rejects wallet transaction requests with no bearer token', function () {
    $this->getJson(API_V1_WALLET_TRANSACTIONS_ENDPOINT)->assertStatus(401);
});

it('lazily creates a wallet on first access', function () {
    $token = $this->clerkToken(['sub' => 'user_wallet_new']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_WALLET_ENDPOINT)
        ->assertStatus(200)
        ->assertJsonPath('data.balance', 0)
        ->assertJsonPath('data.currency', 'tokens');

    $user = User::where('clerk_user_id', 'user_wallet_new')->firstOrFail();
    expect(Wallet::where('user_id', $user->id)->exists())->toBeTrue();
});

it('returns the real balance and currency for a user with existing transactions', function () {
    $token = $this->clerkToken(['sub' => 'user_wallet_balance']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'user_wallet_balance')->firstOrFail();

    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => 250, 'currency' => 'tokens']);
    WalletTransaction::factory()->create(['wallet_id' => $wallet->id, 'amount' => 250, 'balance_after' => 250]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_WALLET_ENDPOINT)
        ->assertStatus(200)
        ->assertJsonPath('data.balance', 250)
        ->assertJsonPath('data.currency', 'tokens');
});

it('lists wallet transactions newest first with pagination meta', function () {
    $token = $this->clerkToken(['sub' => 'user_wallet_tx']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'user_wallet_tx')->firstOrFail();
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);

    $older = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => WalletTransactionType::TopUp,
        'amount' => 100,
        'balance_after' => 100,
        'created_at' => now()->subMinute(),
    ]);
    $newer = WalletTransaction::factory()->create([
        'wallet_id' => $wallet->id,
        'type' => WalletTransactionType::Bonus,
        'amount' => 50,
        'balance_after' => 150,
        'created_at' => now(),
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_WALLET_TRANSACTIONS_ENDPOINT.'?per_page=1')
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.id', $newer->id);
    $response->assertJsonPath('meta.per_page', 1);
    $response->assertJsonPath('meta.has_more_pages', true);
    expect($response->json('meta.next_cursor'))->not->toBeNull();

    $next = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_WALLET_TRANSACTIONS_ENDPOINT.'?per_page=1&cursor='.$response->json('meta.next_cursor'))
        ->assertStatus(200);

    $next->assertJsonPath('data.0.id', $older->id);
    $next->assertJsonPath('meta.has_more_pages', false);
});

it('returns an empty list when the wallet has no transactions', function () {
    $token = $this->clerkToken(['sub' => 'user_wallet_empty']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_WALLET_TRANSACTIONS_ENDPOINT)
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(0);
    $response->assertJsonPath('meta.has_more_pages', false);
    expect($response->json('meta.next_cursor'))->toBeNull();
});

it('denies viewing a wallet belonging to another user', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $wallet = Wallet::factory()->create(['user_id' => $owner->id]);

    expect((new WalletPolicy)->view($intruder, $wallet))->toBeFalse();
    expect((new WalletPolicy)->view($owner, $wallet))->toBeTrue();
});
