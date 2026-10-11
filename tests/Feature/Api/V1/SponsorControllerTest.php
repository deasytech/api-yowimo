<?php

use App\Enums\PartyStatus;
use App\Enums\SponsorshipInviteStatus;
use App\Models\Party;
use App\Models\SponsorshipInvite;
use App\Models\User;
use Tests\Support\FakesClerk;

const API_V1_SPONSORS_ME_ENDPOINT = '/api/v1/sponsors/me';
const API_V1_SPONSORS_ME_SPONSORSHIPS_ENDPOINT = '/api/v1/sponsors/me/sponsorships';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects viewing sponsor data with no bearer token', function () {
    $this->getJson(API_V1_SPONSORS_ME_ENDPOINT)->assertStatus(401);
});

it('summarizes tokens spent, parties sponsored, and players covered', function () {
    $token = $this->clerkToken(['sub' => 'user_sponsor_summary']);
    $sponsor = authAs('user_sponsor_summary');
    $host = User::factory()->create();

    $fullPartyParty = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'max_players' => 8, 'sponsorship_scope' => 'full_party']);
    SponsorshipInvite::create([
        'party_id' => $fullPartyParty->id,
        'scope' => 'full_party',
        'amount' => 100,
        'status' => SponsorshipInviteStatus::Paid,
        'sponsor_id' => $sponsor->id,
        'paid_at' => now(),
        'token' => 'SUMMARYFULL1',
        'expires_at' => now()->addDay(),
    ]);

    $creationFeeParty = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'sponsorship_scope' => 'creation_fee']);
    SponsorshipInvite::create([
        'party_id' => $creationFeeParty->id,
        'scope' => 'creation_fee',
        'amount' => 30,
        'status' => SponsorshipInviteStatus::Paid,
        'sponsor_id' => $sponsor->id,
        'paid_at' => now(),
        'token' => 'SUMMARYCREATION1',
        'expires_at' => now()->addDay(),
    ]);

    // A pending (unpaid) invite from this sponsor should not count toward the summary.
    SponsorshipInvite::create([
        'party_id' => $creationFeeParty->id,
        'scope' => 'creation_fee',
        'amount' => 30,
        'status' => SponsorshipInviteStatus::Pending,
        'token' => 'SUMMARYPENDING1',
        'expires_at' => now()->addDay(),
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_SPONSORS_ME_ENDPOINT)
        ->assertStatus(200)
        ->assertJsonPath('data.parties_count', 2)
        ->assertJsonPath('data.tokens_spent', 130)
        ->assertJsonPath('data.players_covered', 7);
});

it('lists the sponsorships paid by the viewer', function () {
    $token = $this->clerkToken(['sub' => 'user_sponsor_list']);
    $sponsor = authAs('user_sponsor_list');
    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'title' => 'Sponsored List Party', 'sponsorship_scope' => 'creation_fee']);
    SponsorshipInvite::create([
        'party_id' => $party->id,
        'scope' => 'creation_fee',
        'amount' => 30,
        'status' => SponsorshipInviteStatus::Paid,
        'sponsor_id' => $sponsor->id,
        'paid_at' => now(),
        'token' => 'LISTEDINVITE1',
        'expires_at' => now()->addDay(),
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_SPONSORS_ME_SPONSORSHIPS_ENDPOINT)
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.tokens_spent', 30)
        ->assertJsonPath('data.0.status', 'paid')
        ->assertJsonPath('data.0.party.title', 'Sponsored List Party');
});
