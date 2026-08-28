<?php

use App\Enums\PartyStatus;
use App\Enums\WalletTransactionType;
use App\Models\Notification;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\TokenBundle;
use App\Models\User;
use App\Services\Parties\PartyMembershipService;
use App\Services\Purchase\PurchaseService;
use App\Services\Wallet\WalletService;

it('persists an in-app notification alongside the push notification when WalletCredited fires', function () {
    $user = User::factory()->create();

    $transaction = app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);

    $notification = Notification::where('user_id', $user->id)->where('type', 'wallet.credited')->first();

    expect($notification)->not->toBeNull()
        ->title->toBe('Wallet credited')
        ->metadata->toBe(['wallet_transaction_id' => $transaction->id]);
});

it('persists an in-app notification for the host when PartyMemberJoined fires', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'max_players' => 8, 'players_count' => 1]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    $joiner = User::factory()->create();

    app(PartyMembershipService::class)->join($joiner, $party);

    $notification = Notification::where('user_id', $host->id)->where('type', 'party.member_joined')->first();

    expect($notification)->not->toBeNull()
        ->title->toBe('New party member');
});

it('persists a purchase-completed in-app notification but not a duplicate wallet-credited one', function () {
    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create();

    app(PurchaseService::class)->purchase($user, $bundle, 'in_app_suppression_test_1');

    expect(Notification::where('user_id', $user->id)->where('type', 'purchase.completed')->exists())->toBeTrue();
    expect(Notification::where('user_id', $user->id)->where('type', 'wallet.credited')->exists())->toBeFalse();
});
