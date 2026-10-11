<?php

use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Enums\SponsorshipInviteStatus;
use App\Enums\WalletTransactionType;
use App\Models\GameType;
use App\Models\Party;
use App\Models\SponsorshipInvite;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Tests\Support\FakesClerk;

const API_V1_PARTIES_ENDPOINT_SPONSORSHIP = '/api/v1/parties';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function sponsorshipInvitesEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/sponsorship-invites";
}

function sponsorshipInviteShowEndpoint(SponsorshipInvite $invite): string
{
    return "/api/v1/sponsorship-invites/{$invite->token}";
}

function sponsorshipInvitePayEndpoint(SponsorshipInvite $invite): string
{
    return "/api/v1/sponsorship-invites/{$invite->token}/pay";
}

function fundWallet(User $user, int $balance): Wallet
{
    $wallet = Wallet::factory()->create(['user_id' => $user->id, 'balance' => $balance]);
    WalletTransaction::factory()->create(['wallet_id' => $wallet->id, 'amount' => $balance, 'balance_after' => $balance]);

    return $wallet;
}

it('creates a sponsored party in pending_sponsorship status without charging the host', function () {
    $token = $this->clerkToken(['sub' => 'user_sponsor_create_party']);
    $user = authAs('user_sponsor_create_party');
    $gameType = GameType::factory()->create(['cost' => 30]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PARTIES_ENDPOINT_SPONSORSHIP, [
            'title' => 'Sponsored Party',
            'game_type_id' => $gameType->id,
            'mode' => 'online',
            'visibility' => 'public',
            'entry_fee' => 10,
            'sponsorship_scope' => 'creation_fee',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.status', 'pending_sponsorship')
        ->assertJsonPath('data.entry_fee', 10)
        ->assertJsonPath('data.sponsorship', null);

    expect($user->wallet()->exists())->toBeFalse();
});

it('creates a creation_fee sponsorship invite with the game types cost as amount', function () {
    [$host, $hostToken] = [authAs('user_sponsor_invite_host'), $this->clerkToken(['sub' => 'user_sponsor_invite_host'])];
    $gameType = GameType::factory()->create(['cost' => 30]);
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'status' => PartyStatus::PendingSponsorship,
        'game_type_id' => $gameType->id,
        'sponsorship_scope' => 'creation_fee',
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(sponsorshipInvitesEndpoint($party), ['scope' => 'creation_fee'])
        ->assertStatus(201)
        ->assertJsonPath('data.scope', 'creation_fee')
        ->assertJsonPath('data.amount', 30)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.party.id', $party->id);
});

it('creates a full_party sponsorship invite covering every guest slot at the entry fee', function () {
    [$host, $hostToken] = [authAs('user_sponsor_invite_full'), $this->clerkToken(['sub' => 'user_sponsor_invite_full'])];
    $gameType = GameType::factory()->create(['cost' => 30]);
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'status' => PartyStatus::PendingSponsorship,
        'game_type_id' => $gameType->id,
        'entry_fee' => 10,
        'max_players' => 8,
        'sponsorship_scope' => 'full_party',
    ]);

    // amount = creation_fee (30) + entry_fee (10) * (max_players - 1) (7) = 100
    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(sponsorshipInvitesEndpoint($party), ['scope' => 'full_party'])
        ->assertStatus(201)
        ->assertJsonPath('data.amount', 100);
});

it('creates a full_party sponsorship invite using the per-guest cost constant for a free party', function () {
    [$host, $hostToken] = [authAs('user_sponsor_invite_free_full'), $this->clerkToken(['sub' => 'user_sponsor_invite_free_full'])];
    $gameType = GameType::factory()->create(['cost' => 30]);
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'status' => PartyStatus::PendingSponsorship,
        'game_type_id' => $gameType->id,
        'entry_fee' => 0,
        'max_players' => 8,
        'sponsorship_scope' => 'full_party',
    ]);

    // amount = creation_fee (30) + per_guest_cost (10) * (max_players - 1) (7) = 100
    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(sponsorshipInvitesEndpoint($party), ['scope' => 'full_party'])
        ->assertStatus(201)
        ->assertJsonPath('data.amount', 100);
});

it('returns the existing pending invite instead of creating a duplicate for the same scope', function () {
    [$host, $hostToken] = [authAs('user_sponsor_invite_dedup'), $this->clerkToken(['sub' => 'user_sponsor_invite_dedup'])];
    $gameType = GameType::factory()->create(['cost' => 15]);
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'status' => PartyStatus::PendingSponsorship,
        'game_type_id' => $gameType->id,
        'sponsorship_scope' => 'creation_fee',
    ]);

    $first = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(sponsorshipInvitesEndpoint($party), ['scope' => 'creation_fee'])
        ->assertStatus(201)
        ->json('data.token');

    $second = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(sponsorshipInvitesEndpoint($party), ['scope' => 'creation_fee'])
        ->assertStatus(201)
        ->json('data.token');

    expect($first)->toBe($second);
    expect(SponsorshipInvite::where('party_id', $party->id)->count())->toBe(1);
});

it('forbids a non-host from creating a sponsorship invite', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::PendingSponsorship, 'sponsorship_scope' => 'creation_fee']);
    $token = $this->clerkToken(['sub' => 'user_sponsor_invite_nonhost']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(sponsorshipInvitesEndpoint($party), ['scope' => 'creation_fee'])
        ->assertStatus(403);
});

it('rejects a sponsorship invite scope that does not match the party', function () {
    [$host, $hostToken] = [authAs('user_sponsor_invite_mismatch'), $this->clerkToken(['sub' => 'user_sponsor_invite_mismatch'])];
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'status' => PartyStatus::PendingSponsorship,
        'sponsorship_scope' => 'creation_fee',
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(sponsorshipInvitesEndpoint($party), ['scope' => 'full_party'])
        ->assertStatus(422);
});

it('lets any authenticated user view an invite by token', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'sponsorship_scope' => 'creation_fee']);
    $invite = SponsorshipInvite::create([
        'party_id' => $party->id,
        'scope' => 'creation_fee',
        'amount' => 30,
        'status' => SponsorshipInviteStatus::Pending,
        'token' => 'TESTTOKEN123',
        'expires_at' => now()->addDay(),
    ]);
    $token = $this->clerkToken(['sub' => 'user_sponsor_invite_viewer']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(sponsorshipInviteShowEndpoint($invite))
        ->assertStatus(200)
        ->assertJsonPath('data.token', 'TESTTOKEN123')
        ->assertJsonPath('data.amount', 30)
        ->assertJsonPath('data.url', config('services.sponsorship.web_url').'/TESTTOKEN123');
});

it('pays a sponsorship invite, debits the sponsor, and activates the party', function () {
    $host = User::factory()->create();
    $gameType = GameType::factory()->create(['cost' => 30]);
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'status' => PartyStatus::PendingSponsorship,
        'visibility' => PartyVisibility::Public,
        'game_type_id' => $gameType->id,
        'sponsorship_scope' => 'creation_fee',
        'starts_at' => null,
    ]);
    $invite = SponsorshipInvite::create([
        'party_id' => $party->id,
        'scope' => 'creation_fee',
        'amount' => 30,
        'status' => SponsorshipInviteStatus::Pending,
        'token' => 'PAYTOKEN123',
        'expires_at' => now()->addDay(),
    ]);

    $sponsorToken = $this->clerkToken(['sub' => 'user_sponsor_payer']);
    $sponsor = authAs('user_sponsor_payer');
    fundWallet($sponsor, 50);

    $this->withHeader('Authorization', "Bearer {$sponsorToken}")
        ->withHeader('Idempotency-Key', 'pay-key-1')
        ->postJson(sponsorshipInvitePayEndpoint($invite), [])
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'paid');

    expect($sponsor->wallet->fresh()->balance)->toBe(20);
    expect($party->fresh()->status)->toBe(PartyStatus::Live);

    $transaction = WalletTransaction::where('wallet_id', $sponsor->wallet->id)
        ->where('type', WalletTransactionType::Sponsor)
        ->firstOrFail();
    expect($transaction->amount)->toBe(-30);
});

it('rejects the host paying their own sponsorship invite', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_sponsor_host_pay_own']);
    $host = authAs('user_sponsor_host_pay_own');
    fundWallet($host, 100);
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::PendingSponsorship, 'sponsorship_scope' => 'creation_fee']);
    $invite = SponsorshipInvite::create([
        'party_id' => $party->id,
        'scope' => 'creation_fee',
        'amount' => 30,
        'status' => SponsorshipInviteStatus::Pending,
        'token' => 'OWNTOKEN123',
        'expires_at' => now()->addDay(),
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->withHeader('Idempotency-Key', 'pay-key-own')
        ->postJson(sponsorshipInvitePayEndpoint($invite), [])
        ->assertStatus(422)
        ->assertJson(['message' => "You can't sponsor your own party."]);
});

it('returns 422 insufficient token balance when the sponsor cannot afford the invite', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::PendingSponsorship, 'sponsorship_scope' => 'creation_fee']);
    $invite = SponsorshipInvite::create([
        'party_id' => $party->id,
        'scope' => 'creation_fee',
        'amount' => 30,
        'status' => SponsorshipInviteStatus::Pending,
        'token' => 'POORTOKEN123',
        'expires_at' => now()->addDay(),
    ]);

    $sponsorToken = $this->clerkToken(['sub' => 'user_sponsor_poor']);
    authAs('user_sponsor_poor');

    $this->withHeader('Authorization', "Bearer {$sponsorToken}")
        ->withHeader('Idempotency-Key', 'pay-key-poor')
        ->postJson(sponsorshipInvitePayEndpoint($invite), [])
        ->assertStatus(422)
        ->assertJson(['message' => 'Insufficient token balance.']);

    expect($party->fresh()->status)->toBe(PartyStatus::PendingSponsorship);
});

it('rejects paying an invite that is already paid', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'sponsorship_scope' => 'creation_fee']);
    $firstSponsor = User::factory()->create();
    $invite = SponsorshipInvite::create([
        'party_id' => $party->id,
        'scope' => 'creation_fee',
        'amount' => 30,
        'status' => SponsorshipInviteStatus::Paid,
        'sponsor_id' => $firstSponsor->id,
        'paid_at' => now(),
        'token' => 'ALREADYPAID123',
        'expires_at' => now()->addDay(),
    ]);

    $sponsorToken = $this->clerkToken(['sub' => 'user_sponsor_already_paid']);
    $sponsor = authAs('user_sponsor_already_paid');
    fundWallet($sponsor, 100);

    $this->withHeader('Authorization', "Bearer {$sponsorToken}")
        ->withHeader('Idempotency-Key', 'pay-key-already-paid')
        ->postJson(sponsorshipInvitePayEndpoint($invite), [])
        ->assertStatus(422)
        ->assertJson(['message' => 'This sponsorship invite is no longer available.']);
});

it('rejects creating a sponsorship invite whose computed amount is zero', function () {
    [$host, $hostToken] = [authAs('user_sponsor_invite_zero'), $this->clerkToken(['sub' => 'user_sponsor_invite_zero'])];
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'status' => PartyStatus::PendingSponsorship,
        'game_type_id' => null,
        'sponsorship_scope' => 'creation_fee',
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(sponsorshipInvitesEndpoint($party), ['scope' => 'creation_fee'])
        ->assertStatus(422)
        ->assertJson(['message' => 'This party has nothing to sponsor.']);

    expect(SponsorshipInvite::where('party_id', $party->id)->exists())->toBeFalse();
});

it('rejects paying a stale pending invite once the party has already been activated by another one', function () {
    $host = User::factory()->create();
    $gameType = GameType::factory()->create(['cost' => 30]);
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'status' => PartyStatus::PendingSponsorship,
        'game_type_id' => $gameType->id,
        'sponsorship_scope' => 'creation_fee',
    ]);

    // Two pending invites for the same party/scope — the kind of duplicate
    // createInvite()'s row lock now prevents going forward, but this
    // simulates one that slipped through (or was created before the fix).
    $firstInvite = SponsorshipInvite::create([
        'party_id' => $party->id, 'scope' => 'creation_fee', 'amount' => 30,
        'status' => SponsorshipInviteStatus::Paid, 'sponsor_id' => User::factory()->create()->id,
        'paid_at' => now(), 'token' => 'FIRSTPAID123', 'expires_at' => now()->addDay(),
    ]);
    $staleInvite = SponsorshipInvite::create([
        'party_id' => $party->id, 'scope' => 'creation_fee', 'amount' => 30,
        'status' => SponsorshipInviteStatus::Pending, 'token' => 'STALEPENDING123', 'expires_at' => now()->addDay(),
    ]);
    $party->update(['status' => PartyStatus::Live]);

    $sponsorToken = $this->clerkToken(['sub' => 'user_sponsor_stale_invite']);
    $sponsor = authAs('user_sponsor_stale_invite');
    fundWallet($sponsor, 100);

    $this->withHeader('Authorization', "Bearer {$sponsorToken}")
        ->withHeader('Idempotency-Key', 'pay-key-stale')
        ->postJson(sponsorshipInvitePayEndpoint($staleInvite), [])
        ->assertStatus(422)
        ->assertJson(['message' => 'This sponsorship invite is no longer available.']);

    expect($sponsor->wallet->fresh()->balance)->toBe(100);
    expect($staleInvite->fresh()->status)->toBe(SponsorshipInviteStatus::Pending);
    expect($firstInvite->id)->not->toBe($staleInvite->id);
});

it('resolves SponsorshipInvite::factory() correctly', function () {
    $invite = SponsorshipInvite::factory()->paid()->create();

    expect($invite)->toBeInstanceOf(SponsorshipInvite::class);
    expect($invite->status)->toBe(SponsorshipInviteStatus::Paid);
    expect($invite->sponsor_id)->not->toBeNull();
});
