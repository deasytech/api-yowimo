<?php

use App\Filament\Resources\WalletTransactions\WalletTransactionResource;
use App\Models\User;
use App\Models\WalletTransaction;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'password' => 'password',
        'is_admin' => true,
    ]);
});

it('never allows creating, editing, or deleting wallet transactions from the panel', function () {
    expect(WalletTransactionResource::canCreate())->toBeFalse();

    $transaction = WalletTransaction::factory()->create();

    expect(WalletTransactionResource::canEdit($transaction))->toBeFalse()
        ->and(WalletTransactionResource::canDelete($transaction))->toBeFalse()
        ->and(WalletTransactionResource::canDeleteAny())->toBeFalse();
});

it('does not register create or edit routes for wallet transactions', function () {
    $transaction = WalletTransaction::factory()->create();

    $this->actingAs($this->admin, 'web')
        ->get('/admin/wallet-transactions/create')
        ->assertNotFound();

    $this->actingAs($this->admin, 'web')
        ->get("/admin/wallet-transactions/{$transaction->id}/edit")
        ->assertNotFound();
});

it('lets an admin view the wallet transaction ledger', function () {
    $transaction = WalletTransaction::factory()->create();

    $this->actingAs($this->admin, 'web')
        ->get('/admin/wallet-transactions')
        ->assertOk();

    $this->actingAs($this->admin, 'web')
        ->get("/admin/wallet-transactions/{$transaction->id}")
        ->assertOk();
});
