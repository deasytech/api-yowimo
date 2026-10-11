<?php

use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Enums\SponsorshipInviteStatus;
use App\Enums\WalletTransactionType;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\SponsorshipInvite;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function entryFeeJoinEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/join";
}

function entryFeeLeaveEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/leave";
}

function entryFeeCancelEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/cancel";
}

function entryFeeEndEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/end";
}

function fundWalletWith(User $user, int $balance): Wallet
{
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => $balance]);
    WalletTransaction::factory()->create(['wallet_id' => $wallet->id, 'amount' => $balance, 'balance_after' => $balance]);

    return $wallet;
}

it('charges the guest the entry fee on join and records a party_entry transaction', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'entry_fee' => 10,
        'players_count' => 1,
    ]);

    $token = $this->clerkToken(['sub' => 'user_entry_fee_payer']);
    $guest = authAs('user_entry_fee_payer');
    fundWalletWith($guest, 50);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(entryFeeJoinEndpoint($party))
        ->assertStatus(200);

    expect($guest->wallet->fresh()->balance)->toBe(40);

    $transaction = WalletTransaction::where('wallet_id', $guest->wallet->id)
        ->where('type', WalletTransactionType::PartyEntry)
        ->firstOrFail();
    expect($transaction->amount)->toBe(-10);
    expect((int) $transaction->reference_id)->toBe($party->id);
});

it('does not charge the entry fee again on rejoin after leaving', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'entry_fee' => 10,
        'players_count' => 1,
    ]);

    $token = $this->clerkToken(['sub' => 'user_entry_fee_rejoiner']);
    $guest = authAs('user_entry_fee_rejoiner');
    fundWalletWith($guest, 50);

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(entryFeeJoinEndpoint($party))->assertStatus(200);
    $this->withHeader('Authorization', "Bearer {$token}")->deleteJson(entryFeeLeaveEndpoint($party))->assertStatus(200);
    $this->withHeader('Authorization', "Bearer {$token}")->postJson(entryFeeJoinEndpoint($party))->assertStatus(200);

    expect($guest->wallet->fresh()->balance)->toBe(40);
    expect(WalletTransaction::where('wallet_id', $guest->wallet->id)->where('type', WalletTransactionType::PartyEntry)->count())->toBe(1);
});

it('returns 422 insufficient token balance when the guest cannot afford the entry fee', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'entry_fee' => 10,
        'players_count' => 1,
    ]);

    $token = $this->clerkToken(['sub' => 'user_entry_fee_poor']);
    authAs('user_entry_fee_poor');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(entryFeeJoinEndpoint($party))
        ->assertStatus(422)
        ->assertJson(['message' => 'Insufficient token balance.']);

    expect($party->fresh()->players_count)->toBe(1);
});

it('skips the entry fee charge once a full_party sponsor has paid', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'entry_fee' => 10,
        'players_count' => 1,
        'sponsorship_scope' => 'full_party',
    ]);
    $sponsor = User::factory()->create();
    SponsorshipInvite::create([
        'party_id' => $party->id,
        'scope' => 'full_party',
        'amount' => 70,
        'status' => SponsorshipInviteStatus::Paid,
        'sponsor_id' => $sponsor->id,
        'paid_at' => now(),
        'token' => 'FULLPARTYPAID1',
        'expires_at' => now()->addDay(),
    ]);

    $token = $this->clerkToken(['sub' => 'user_entry_fee_covered']);
    $guest = authAs('user_entry_fee_covered');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(entryFeeJoinEndpoint($party))
        ->assertStatus(200);

    expect($guest->wallet()->exists())->toBeFalse();
});

it('refunds the guest and the sponsor when the host cancels', function () {
    // The host is the only actor that needs to authenticate a real request
    // here (the cancel itself) — the guest's prior entry-fee charge and the
    // sponsor's prior payment are set up directly, exactly as join() and
    // pay() would have left them, since join()/pay() charging correctly is
    // already covered by their own dedicated tests; this test is only about
    // what cancel() does with charges that already happened.
    $hostToken = $this->clerkToken(['sub' => 'user_cancel_refund_host']);
    $host = authAs('user_cancel_refund_host');
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Scheduled,
        'entry_fee' => 10,
        'players_count' => 2,
        'sponsorship_scope' => 'creation_fee',
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $guest = User::factory()->create();
    fundWalletWith($guest, 50);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $guest->id]);
    app(WalletService::class)->debit($guest, 10, WalletTransactionType::PartyEntry, reference: $party);
    expect($guest->wallet->fresh()->balance)->toBe(40);

    $sponsor = User::factory()->create();
    fundWalletWith($sponsor, 100);
    app(WalletService::class)->debit($sponsor, 30, WalletTransactionType::Sponsor, reference: $party);
    $invite = SponsorshipInvite::create([
        'party_id' => $party->id,
        'scope' => 'creation_fee',
        'amount' => 30,
        'status' => SponsorshipInviteStatus::Paid,
        'sponsor_id' => $sponsor->id,
        'paid_at' => now(),
        'token' => 'CANCELREFUND1',
        'expires_at' => now()->addDay(),
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(entryFeeCancelEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');

    expect($guest->wallet->fresh()->balance)->toBe(50);
    expect($sponsor->wallet->fresh()->balance)->toBe(100);
    expect($invite->fresh()->status)->toBe(SponsorshipInviteStatus::Cancelled);
});

it('refunds the sponsor for unfilled guest slots when a full_party-sponsored party ends', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_end_refund_host']);
    $host = authAs('user_end_refund_host');
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'entry_fee' => 10,
        'max_players' => 8,
        'players_count' => 3,
        'sponsorship_scope' => 'full_party',
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $sponsor = User::factory()->create();
    fundWalletWith($sponsor, 100);
    app(WalletService::class)->debit($sponsor, 70, WalletTransactionType::Sponsor, reference: $party);
    SponsorshipInvite::create([
        'party_id' => $party->id,
        'scope' => 'full_party',
        'amount' => 70,
        'status' => SponsorshipInviteStatus::Paid,
        'sponsor_id' => $sponsor->id,
        'paid_at' => now(),
        'token' => 'ENDREFUND1',
        'expires_at' => now()->addDay(),
    ]);

    // 3 players already in (host + 2 guests), max 8 -> 5 slots never filled -> refund 5 * 10 = 50.
    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(entryFeeEndEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'ended');

    expect($sponsor->wallet->fresh()->balance)->toBe(80);
});
