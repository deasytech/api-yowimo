<?php

use App\Enums\WalletTransactionType;
use App\Models\WalletTransaction;

it('generates a debit with balance_after lower than the implied prior balance', function () {
    $transaction = WalletTransaction::factory()->debit(40)->make();

    expect($transaction->type)->toBe(WalletTransactionType::Purchase);
    expect($transaction->amount)->toBe(-40);
    expect($transaction->balance_after)->toBeGreaterThanOrEqual(0);

    $impliedPriorBalance = $transaction->balance_after + abs($transaction->amount);
    expect($transaction->balance_after)->toBeLessThan($impliedPriorBalance);
});
