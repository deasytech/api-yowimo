<?php

use App\Enums\PartyStatus;
use App\Enums\WalletTransactionType;
use App\Models\Party;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;

it('lets an admin load the dashboard with seeded data across all widgets', function () {
    $admin = User::factory()->create([
        'password' => 'password',
        'is_admin' => true,
    ]);

    Party::factory()->create(['status' => PartyStatus::Live]);
    Party::factory()->create(['status' => PartyStatus::Ended]);
    Party::factory()->create(['status' => PartyStatus::Cancelled]);

    $wallet = Wallet::factory()->create();
    WalletTransaction::factory()->create(['wallet_id' => $wallet->id, 'type' => WalletTransactionType::TopUp, 'amount' => 500]);
    WalletTransaction::factory()->create(['wallet_id' => $wallet->id, 'type' => WalletTransactionType::Purchase, 'amount' => -200]);

    $this->actingAs($admin, 'web')
        ->get('/admin')
        ->assertOk()
        ->assertSee('Total Users')
        ->assertSee('Total Parties')
        ->assertSee('Tokens Purchased')
        ->assertSee('Party Completion Rate');
});

it('lets an admin load the dashboard with no data at all', function () {
    $admin = User::factory()->create([
        'password' => 'password',
        'is_admin' => true,
    ]);

    $this->actingAs($admin, 'web')
        ->get('/admin')
        ->assertOk();
});
