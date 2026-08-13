<?php

use App\Models\Pack;
use App\Models\PackCard;
use App\Models\PackPurchase;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function purchasePackEndpoint(Pack $pack): string
{
    return "/api/v1/packs/{$pack->id}/purchase";
}

it('rejects purchase requests with no bearer token', function () {
    $pack = Pack::factory()->create();

    $this->postJson(purchasePackEndpoint($pack))->assertStatus(401);
});

it('requires an idempotency key header', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_purchase_no_key']);
    $pack = Pack::factory()->create(['price' => 100]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(purchasePackEndpoint($pack))
        ->assertStatus(422)
        ->assertJsonValidationErrors('idempotency_key');
});

it('purchases a pack, debits the wallet, and records ownership', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_purchase_success']);
    $user = User::factory()->create(['clerk_user_id' => 'user_pack_purchase_success']);
    Wallet::factory()->create(['user_id' => $user->id, 'balance' => 200]);
    WalletTransaction::factory()->create(['wallet_id' => $user->wallet->id, 'amount' => 200, 'balance_after' => 200]);

    $pack = Pack::factory()->create(['name' => 'Spicy Pack', 'price' => 150]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'pack_purchase_key_1')
        ->postJson(purchasePackEndpoint($pack))
        ->assertStatus(201);

    $response->assertJsonPath('data.pack_id', $pack->id);
    $response->assertJsonPath('data.wallet_transaction.amount', -150);
    $response->assertJsonPath('data.wallet_transaction.balance_after', 50);

    expect($user->wallet->fresh()->balance)->toBe(50);
    expect(PackPurchase::where('pack_id', $pack->id)->where('user_id', $user->id)->exists())->toBeTrue();
});

it('does not double-debit when the same idempotency key is retried', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_purchase_retry']);
    $user = User::factory()->create(['clerk_user_id' => 'user_pack_purchase_retry']);
    Wallet::factory()->create(['user_id' => $user->id, 'balance' => 300]);
    WalletTransaction::factory()->create(['wallet_id' => $user->wallet->id, 'amount' => 300, 'balance_after' => 300]);

    $pack = Pack::factory()->create(['price' => 100]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'pack_purchase_retry_key')
        ->postJson(purchasePackEndpoint($pack))
        ->assertStatus(201);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'pack_purchase_retry_key')
        ->postJson(purchasePackEndpoint($pack))
        ->assertStatus(409);

    expect($user->wallet->fresh()->balance)->toBe(200);
    expect(PackPurchase::where('pack_id', $pack->id)->where('user_id', $user->id)->count())->toBe(1);
});

it('returns a clean error and does not create an ownership record when the balance is insufficient', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_purchase_poor']);
    $user = User::factory()->create(['clerk_user_id' => 'user_pack_purchase_poor']);
    Wallet::factory()->create(['user_id' => $user->id, 'balance' => 10]);
    WalletTransaction::factory()->create(['wallet_id' => $user->wallet->id, 'amount' => 10, 'balance_after' => 10]);

    $pack = Pack::factory()->create(['price' => 500]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'pack_purchase_poor_key')
        ->postJson(purchasePackEndpoint($pack))
        ->assertStatus(422);

    expect($user->wallet->fresh()->balance)->toBe(10);
    expect(PackPurchase::where('pack_id', $pack->id)->where('user_id', $user->id)->exists())->toBeFalse();
});

it('rejects purchasing a pack the user already owns without a second debit', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_purchase_owned']);
    $user = User::factory()->create(['clerk_user_id' => 'user_pack_purchase_owned']);
    Wallet::factory()->create(['user_id' => $user->id, 'balance' => 500]);
    WalletTransaction::factory()->create(['wallet_id' => $user->wallet->id, 'amount' => 500, 'balance_after' => 500]);

    $pack = Pack::factory()->create(['price' => 100]);
    PackPurchase::factory()->create(['pack_id' => $pack->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'pack_purchase_owned_key')
        ->postJson(purchasePackEndpoint($pack))
        ->assertStatus(409);

    expect($user->wallet->fresh()->balance)->toBe(500);
});

it('returns 404 when purchasing an inactive pack', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_purchase_inactive']);
    $pack = Pack::factory()->create(['is_active' => false]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'pack_purchase_inactive_key')
        ->postJson(purchasePackEndpoint($pack))
        ->assertStatus(404);
});

it('returns 404 when purchasing an unknown pack', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_purchase_unknown']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'pack_purchase_unknown_key')
        ->postJson('/api/v1/packs/999999/purchase')
        ->assertStatus(404);
});

it('only returns preview cards to a viewer who has not purchased the pack', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_viewer_not_owner']);
    $pack = Pack::factory()->create();
    PackCard::factory()->preview()->create(['pack_id' => $pack->id, 'text' => 'Preview truth?']);
    PackCard::factory()->create(['pack_id' => $pack->id, 'is_preview' => false, 'text' => 'Full dare.']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/packs/{$pack->id}")
        ->assertStatus(200);

    $response->assertJsonPath('data.owned_by_me', false);
    expect($response->json('data.preview_cards'))->toHaveCount(1);
    $response->assertJsonPath('data.preview_cards.0.text', 'Preview truth?');
});

it('returns full pack content once the viewer owns the pack', function () {
    $token = $this->clerkToken(['sub' => 'user_pack_viewer_owner']);
    $user = User::factory()->create(['clerk_user_id' => 'user_pack_viewer_owner']);
    $pack = Pack::factory()->create();
    PackPurchase::factory()->create(['pack_id' => $pack->id, 'user_id' => $user->id]);

    PackCard::factory()->preview()->create(['pack_id' => $pack->id, 'text' => 'Preview truth?']);
    PackCard::factory()->create(['pack_id' => $pack->id, 'is_preview' => false, 'text' => 'Full dare.']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/packs/{$pack->id}")
        ->assertStatus(200);

    $response->assertJsonPath('data.owned_by_me', true);
    expect($response->json('data.preview_cards'))->toHaveCount(2);
});
