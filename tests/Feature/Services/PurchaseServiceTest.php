<?php

use App\Exceptions\Api\IdempotencyKeyConflictException;
use App\Models\TokenBundle;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Purchase\PaymentProvider;
use App\Services\Purchase\PurchaseService;

it('does not re-charge the payment provider when the same idempotency key is retried', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['tokens' => 250]);

    $provider = Mockery::mock(PaymentProvider::class);
    $provider->shouldReceive('charge')->once()->andReturn(true);
    $this->app->instance(PaymentProvider::class, $provider);

    $first = app(PurchaseService::class)->purchase($user, $bundle, 'purchase_retry_charge_key');
    $second = app(PurchaseService::class)->purchase($user, $bundle, 'purchase_retry_charge_key');

    expect($second->id)->toBe($first->id);
    expect(WalletTransaction::query()->where('idempotency_key', 'purchase_retry_charge_key')->count())->toBe(1);
});

it('rejects a retried purchase whose idempotency key was already used for a different bundle', function () {
    $user = User::factory()->create();
    $bundleA = TokenBundle::factory()->create(['tokens' => 250]);
    $bundleB = TokenBundle::factory()->create(['tokens' => 500]);

    app(PurchaseService::class)->purchase($user, $bundleA, 'purchase_mismatch_key');

    expect(fn () => app(PurchaseService::class)->purchase($user, $bundleB, 'purchase_mismatch_key'))
        ->toThrow(IdempotencyKeyConflictException::class);

    expect(WalletTransaction::query()->where('idempotency_key', 'purchase_mismatch_key')->count())->toBe(1);
});

it('does not leak another user\'s purchase when idempotency keys collide across users', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $bundle = TokenBundle::factory()->create(['tokens' => 250]);

    $transactionA = app(PurchaseService::class)->purchase($userA, $bundle, 'shared_purchase_key');
    $transactionB = app(PurchaseService::class)->purchase($userB, $bundle, 'shared_purchase_key');

    expect($transactionB->id)->not->toBe($transactionA->id);
    expect($transactionB->wallet_id)->not->toBe($transactionA->wallet_id);
    expect(WalletTransaction::query()->where('idempotency_key', 'shared_purchase_key')->count())->toBe(2);
});
