<?php

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
