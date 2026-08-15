<?php

use App\Enums\WalletTransactionType;
use App\Exceptions\Api\IdempotencyKeyConflictException;
use App\Exceptions\Api\InsufficientWalletBalanceException;
use App\Models\TokenBundle;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Database\QueryException;

function walletService(): WalletService
{
    return app(WalletService::class);
}

it('lazily creates a wallet with a zero balance', function () {
    $user = User::factory()->create();

    expect(Wallet::query()->where('user_id', $user->id)->exists())->toBeFalse();

    $balance = walletService()->balance($user);

    expect($balance)->toBe(0);
    expect(Wallet::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

it('credits a wallet and records a ledger entry', function () {
    $user = User::factory()->create();

    $transaction = walletService()->credit($user, 100, WalletTransactionType::TopUp);

    expect($transaction->amount)->toBe(100);
    expect($transaction->balance_after)->toBe(100);
    expect(walletService()->balance($user))->toBe(100);
});

it('debits a wallet when there is sufficient balance', function () {
    $user = User::factory()->create();
    walletService()->credit($user, 100, WalletTransactionType::TopUp);

    $transaction = walletService()->debit($user, 40, WalletTransactionType::Purchase);

    expect($transaction->amount)->toBe(-40);
    expect($transaction->balance_after)->toBe(60);
    expect(walletService()->balance($user))->toBe(60);
});

it('refuses to debit past zero', function () {
    $user = User::factory()->create();
    walletService()->credit($user, 30, WalletTransactionType::TopUp);

    expect(fn () => walletService()->debit($user, 31, WalletTransactionType::Purchase))
        ->toThrow(InsufficientWalletBalanceException::class);

    expect(walletService()->balance($user))->toBe(30);
    expect(WalletTransaction::query()->where('type', WalletTransactionType::Purchase)->count())->toBe(0);
});

it('keeps the cached balance equal to the sum of the ledger after multiple operations', function () {
    $user = User::factory()->create();

    walletService()->credit($user, 200, WalletTransactionType::TopUp);
    walletService()->debit($user, 50, WalletTransactionType::Purchase);
    walletService()->credit($user, 25, WalletTransactionType::Bonus);
    walletService()->debit($user, 10, WalletTransactionType::Purchase);

    $wallet = walletService()->walletFor($user);

    expect($wallet->fresh()->balance)->toBe(165);
    expect($wallet->ledgerBalance())->toBe(165);
});

it('is idempotent when the same idempotency key is credited twice', function () {
    $user = User::factory()->create();

    $first = walletService()->credit($user, 100, WalletTransactionType::TopUp, idempotencyKey: 'evt_123');
    $second = walletService()->credit($user, 100, WalletTransactionType::TopUp, idempotencyKey: 'evt_123');

    expect($first->id)->toBe($second->id);
    expect(walletService()->balance($user))->toBe(100);
    expect(WalletTransaction::query()->where('idempotency_key', 'evt_123')->count())->toBe(1);
});

it('does not leak another user\'s wallet transaction when idempotency keys collide across users', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $transactionA = walletService()->credit($userA, 100, WalletTransactionType::TopUp, idempotencyKey: 'shared_key');
    $transactionB = walletService()->credit($userB, 250, WalletTransactionType::TopUp, idempotencyKey: 'shared_key');

    expect($transactionB->id)->not->toBe($transactionA->id);
    expect($transactionB->wallet_id)->not->toBe($transactionA->wallet_id);
    expect(walletService()->balance($userA))->toBe(100);
    expect(walletService()->balance($userB))->toBe(250);
    expect(WalletTransaction::query()->where('idempotency_key', 'shared_key')->count())->toBe(2);
});

it('rejects reusing an idempotency key for a different reference', function () {
    $user = User::factory()->create();
    $bundleA = TokenBundle::factory()->create();
    $bundleB = TokenBundle::factory()->create();

    walletService()->credit($user, 100, WalletTransactionType::TopUp, reference: $bundleA, idempotencyKey: 'reused_key');

    expect(fn () => walletService()->credit($user, 100, WalletTransactionType::TopUp, reference: $bundleB, idempotencyKey: 'reused_key'))
        ->toThrow(IdempotencyKeyConflictException::class);

    expect(walletService()->balance($user))->toBe(100);
    expect(WalletTransaction::query()->where('idempotency_key', 'reused_key')->count())->toBe(1);
});

it('rebuilds the cached balance from the ledger, proving the column is a derived cache', function () {
    $user = User::factory()->create();

    walletService()->credit($user, 200, WalletTransactionType::TopUp);
    walletService()->debit($user, 75, WalletTransactionType::Purchase);

    $wallet = walletService()->walletFor($user);

    // Simulate cache drift by corrupting the cached column directly.
    $wallet->update(['balance' => 999999]);
    expect($wallet->fresh()->balance)->toBe(999999);

    $rebuilt = walletService()->recalculate($user);

    expect($rebuilt)->toBe(125);
    expect($wallet->fresh()->balance)->toBe(125);
});

it('prevents wallet transactions from being updated', function () {
    $transaction = WalletTransaction::factory()->create();

    expect(fn () => $transaction->update(['amount' => 999]))->toThrow(LogicException::class);
});

it('prevents wallet transactions from being deleted', function () {
    $transaction = WalletTransaction::factory()->create();

    expect(fn () => $transaction->delete())->toThrow(LogicException::class);
});

it('rejects deleting a wallet that still has ledger entries at the database level', function () {
    $user = User::factory()->create();
    walletService()->credit($user, 100, WalletTransactionType::TopUp);

    $wallet = walletService()->walletFor($user);

    expect(fn () => $wallet->delete())->toThrow(QueryException::class);
    expect(Wallet::query()->whereKey($wallet->id)->exists())->toBeTrue();
});
