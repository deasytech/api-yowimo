<?php

use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function tvPairingCodeEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/tv-pairing-code";
}

it('rejects tv pairing requests with no bearer token', function () {
    $party = Party::factory()->create(['status' => PartyStatus::Live]);

    $this->getJson(tvPairingCodeEndpoint($party))->assertStatus(401);
});

it('issues a 6-digit pairing code to an active member of a live in-person party', function () {
    $token = $this->clerkToken(['sub' => 'tv_pairing_member']);
    $party = Party::factory()->create(['mode' => PartyMode::InPerson, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Public]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();
    $user = User::where('clerk_user_id', 'tv_pairing_member')->firstOrFail();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(tvPairingCodeEndpoint($party))
        ->assertStatus(200)
        ->assertJsonStructure(['data' => ['code', 'expires_at']])
        ->assertJsonPath('data.code', fn (string $code) => (bool) preg_match('/^\d{6}$/', $code));
});

it('returns the same code on repeated calls until it expires', function () {
    $token = $this->clerkToken(['sub' => 'tv_pairing_repeat']);
    $party = Party::factory()->create(['mode' => PartyMode::Hybrid, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Public]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();
    $user = User::where('clerk_user_id', 'tv_pairing_repeat')->firstOrFail();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id]);

    $first = $this->withHeader('Authorization', "Bearer {$token}")->getJson(tvPairingCodeEndpoint($party))->assertStatus(200);
    $second = $this->withHeader('Authorization', "Bearer {$token}")->getJson(tvPairingCodeEndpoint($party))->assertStatus(200);

    expect($second->json('data.code'))->toBe($first->json('data.code'));

    $this->travel(11)->minutes();

    $third = $this->withHeader('Authorization', "Bearer {$token}")->getJson(tvPairingCodeEndpoint($party))->assertStatus(200);
    expect($third->json('data.code'))->not->toBe($first->json('data.code'));
});

it('forbids a tv pairing request before the party is live', function () {
    $token = $this->clerkToken(['sub' => 'tv_pairing_not_live']);
    $party = Party::factory()->create(['status' => PartyStatus::Scheduled, 'visibility' => PartyVisibility::Public]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();
    $user = User::where('clerk_user_id', 'tv_pairing_not_live')->firstOrFail();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(tvPairingCodeEndpoint($party))
        ->assertStatus(403);
});

it('forbids a non-member from requesting a tv pairing code', function () {
    $token = $this->clerkToken(['sub' => 'tv_pairing_non_member']);
    $party = Party::factory()->create(['status' => PartyStatus::Live, 'visibility' => PartyVisibility::Private]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(tvPairingCodeEndpoint($party))
        ->assertStatus(403);
});
