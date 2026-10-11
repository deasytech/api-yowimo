<?php

use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Enums\SponsorshipInviteStatus;
use App\Enums\WalletTransactionType;
use App\Models\GameType;
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

function createEntryFeeParty(User $host, array $overrides = []): Party
{
    return Party::factory()->create(array_replace([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'entry_fee' => 0,
        'players_count' => 1,
    ], $overrides));
}

function fundWalletWith(User $user, int $balance): Wallet
{
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => $balance]);
    WalletTransaction::factory()->create(['wallet_id' => $wallet->id, 'amount' => $balance, 'balance_after' => $balance]);

    return $wallet;
}

function cancelPartyAsHost(string $hostToken, Party $party): void
{
    test()->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(entryFeeCancelEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');
}

it('charges the correct party on join and records a transaction', function (int $entryFee, ?string $scope, bool $hostPays, WalletTransactionType $type) {
    $host = User::factory()->create();
    $party = createEntryFeeParty($host, [
        'entry_fee' => $entryFee,
        'sponsorship_scope' => $scope,
    ]);

    $token = $this->clerkToken(['sub' => 'user_join_charge_'.$entryFee]);
    $guest = authAs('user_join_charge_'.$entryFee);

    $payer = $hostPays ? $host : $guest;
    fundWalletWith($payer, 50);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(entryFeeJoinEndpoint($party))
        ->assertStatus(200);

    $other = $hostPays ? $guest : $host;

    expect($payer->wallet->fresh()->balance)->toBe(40);
    expect($other->wallet()->exists())->toBeFalse();

    $transaction = WalletTransaction::where('wallet_id', $payer->wallet->id)
        ->where('type', $type)
        ->firstOrFail();
    expect($transaction->amount)->toBe(-10);
    expect((int) $transaction->reference_id)->toBe($party->id);
})->with([
    'paid party charges the guest' => [10, null, false, WalletTransactionType::PartyEntry],
    'free party with creation_fee sponsorship charges the host' => [0, 'creation_fee', true, WalletTransactionType::FreePartyGuestCost],
]);

it('does not re-charge the payer again on rejoin after leaving', function (int $entryFee, ?string $scope, bool $hostPays, WalletTransactionType $type) {
    $host = User::factory()->create();
    $party = createEntryFeeParty($host, [
        'entry_fee' => $entryFee,
        'sponsorship_scope' => $scope,
    ]);

    $token = $this->clerkToken(['sub' => 'user_rejoin_charge_'.$entryFee]);
    $guest = authAs('user_rejoin_charge_'.$entryFee);

    $payer = $hostPays ? $host : $guest;
    fundWalletWith($payer, 50);

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(entryFeeJoinEndpoint($party))->assertStatus(200);
    $this->withHeader('Authorization', "Bearer {$token}")->deleteJson(entryFeeLeaveEndpoint($party))->assertStatus(200);
    $this->withHeader('Authorization', "Bearer {$token}")->postJson(entryFeeJoinEndpoint($party))->assertStatus(200);

    expect($payer->wallet->fresh()->balance)->toBe(40);
    expect(WalletTransaction::where('wallet_id', $payer->wallet->id)->where('type', $type)->count())->toBe(1);
})->with([
    'paid party, guest pays' => [10, null, false, WalletTransactionType::PartyEntry],
    'free party with creation_fee sponsorship, host pays' => [0, 'creation_fee', true, WalletTransactionType::FreePartyGuestCost],
]);

it('returns 422 insufficient token balance when the payer cannot afford the join cost, and the guest is not added', function (int $entryFee, ?string $scope) {
    $host = User::factory()->create();
    $party = createEntryFeeParty($host, [
        'entry_fee' => $entryFee,
        'sponsorship_scope' => $scope,
    ]);

    $token = $this->clerkToken(['sub' => 'user_join_charge_poor_'.$entryFee]);
    authAs('user_join_charge_poor_'.$entryFee);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(entryFeeJoinEndpoint($party))
        ->assertStatus(422)
        ->assertJson(['message' => 'Insufficient token balance.']);

    expect($party->fresh()->players_count)->toBe(1);
})->with([
    'guest cannot afford the paid entry fee' => [10, null],
    'host cannot afford the free-party guest cost' => [0, 'creation_fee'],
]);

it('does not charge the host or guest for a full_party-sponsored party once its sponsor has paid', function (int $entryFee) {
    $host = User::factory()->create();
    fundWalletWith($host, 50);
    $party = createEntryFeeParty($host, [
        'entry_fee' => $entryFee,
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
        'token' => 'FULLPARTYPAID'.$entryFee,
        'expires_at' => now()->addDay(),
    ]);

    $token = $this->clerkToken(['sub' => 'user_entry_fee_covered_'.$entryFee]);
    $guest = authAs('user_entry_fee_covered_'.$entryFee);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(entryFeeJoinEndpoint($party))
        ->assertStatus(200);

    expect($host->wallet->fresh()->balance)->toBe(50);
    expect($guest->wallet()->exists())->toBeFalse();
})->with([
    'paid party' => [10],
    'free party' => [0],
]);

it('refunds the guest and the sponsor when the host cancels', function () {
    // The host is the only actor that needs to authenticate a real request
    // here (the cancel itself) — the guest's prior entry-fee charge and the
    // sponsor's prior payment are set up directly, exactly as join() and
    // pay() would have left them, since join()/pay() charging correctly is
    // already covered by their own dedicated tests; this test is only about
    // what cancel() does with charges that already happened.
    $hostToken = $this->clerkToken(['sub' => 'user_cancel_refund_host']);
    $host = authAs('user_cancel_refund_host');
    $party = createEntryFeeParty($host, [
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

    cancelPartyAsHost($hostToken, $party);

    expect($guest->wallet->fresh()->balance)->toBe(50);
    expect($sponsor->wallet->fresh()->balance)->toBe(100);
    expect($invite->fresh()->status)->toBe(SponsorshipInviteStatus::Cancelled);
});

it('refunds the host for covered free-party guests on cancel, but not their own game-type creation fee', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_cancel_free_party_host']);
    $host = authAs('user_cancel_free_party_host');
    $gameType = GameType::factory()->create(['cost' => 30]);
    fundWalletWith($host, 100);
    $party = createEntryFeeParty($host, [
        'game_type_id' => $gameType->id,
        'status' => PartyStatus::Scheduled,
        'players_count' => 2,
        'sponsorship_scope' => 'creation_fee',
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    app(WalletService::class)->debit($host, 30, WalletTransactionType::PartyEntry, reference: $party);

    $guest = User::factory()->create();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $guest->id]);
    app(WalletService::class)->debit($host, 10, WalletTransactionType::FreePartyGuestCost, reference: $party, idempotencyKey: "party-entry-host-{$party->id}-{$guest->id}");
    expect($host->wallet->fresh()->balance)->toBe(60);

    cancelPartyAsHost($hostToken, $party);

    // The 10-token free-party guest cost comes back; the 30-token creation fee does not.
    expect($host->wallet->fresh()->balance)->toBe(70);
});

it('does not refund the hosts own game-type creation fee when they cancel an unsponsored party', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_cancel_no_host_refund']);
    $host = authAs('user_cancel_no_host_refund');
    fundWalletWith($host, 100);
    $gameType = GameType::factory()->create(['cost' => 30]);

    $response = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson('/api/v1/parties', [
            'title' => 'Unsponsored scheduled party',
            'game_type_id' => $gameType->id,
            'mode' => 'online',
            'visibility' => 'public',
            'starts_at' => now()->addHour()->toISOString(),
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.status', 'scheduled');

    expect($host->wallet->fresh()->balance)->toBe(70);
    $party = Party::findOrFail($response->json('data.id'));

    cancelPartyAsHost($hostToken, $party);

    expect($host->wallet->fresh()->balance)->toBe(70);
});

it('refunds the sponsor for unfilled guest slots at the rate actually paid when a full_party-sponsored party ends', function (int $entryFee) {
    $hostToken = $this->clerkToken(['sub' => 'user_end_refund_host_'.$entryFee]);
    $host = authAs('user_end_refund_host_'.$entryFee);
    $party = createEntryFeeParty($host, [
        'entry_fee' => $entryFee,
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
        'token' => 'ENDREFUND'.$entryFee,
        'expires_at' => now()->addDay(),
    ]);

    // 3 players already in (host + 2 guests), max 8 -> 5 slots never filled -> refund 5 * 10 = 50.
    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(entryFeeEndEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'ended');

    expect($sponsor->wallet->fresh()->balance)->toBe(80);
})->with([
    'paid party, rate is entry_fee' => [10],
    'free party, rate is the per_guest_cost constant' => [0],
]);

it('leaves an ordinary free party with no sponsorship_scope genuinely free for the host', function () {
    $host = User::factory()->create();
    $party = createEntryFeeParty($host);

    $token = $this->clerkToken(['sub' => 'user_plain_free_party_guest']);
    $guest = authAs('user_plain_free_party_guest');

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(entryFeeJoinEndpoint($party))
        ->assertStatus(200);

    expect($host->wallet()->exists())->toBeFalse();
    expect($guest->wallet()->exists())->toBeFalse();
});
