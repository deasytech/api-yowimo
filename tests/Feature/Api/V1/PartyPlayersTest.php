<?php

use App\Enums\PartyMemberStatus;
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

function partyPlayersEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/players";
}

function authAsPlayer(User $user): void
{
    app('auth')->forgetGuards();
    test()->withHeader('Authorization', 'Bearer '.test()->clerkToken(['sub' => $user->clerk_user_id]));
}

it('lists every player with public identity, host flag, and membership status', function () {
    $host = User::factory()->create(['clerk_user_id' => 'players_host', 'display_name' => 'Hosty']);
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Private]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id, 'joined_at' => now()->subMinutes(3)]);
    $member = PartyMember::factory()->create(['party_id' => $party->id, 'joined_at' => now()->subMinutes(2)]);
    $leaver = PartyMember::factory()->left()->create(['party_id' => $party->id, 'joined_at' => now()->subMinute()]);

    authAsPlayer($host);

    $this->getJson(partyPlayersEndpoint($party))
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.user_id', $host->id)
        ->assertJsonPath('data.0.user.display_name', 'Hosty')
        ->assertJsonPath('data.0.is_host', true)
        ->assertJsonPath('data.0.status', 'active')
        ->assertJsonPath('data.1.user_id', $member->user_id)
        ->assertJsonPath('data.1.is_host', false)
        ->assertJsonPath('data.2.user_id', $leaver->user_id)
        ->assertJsonPath('data.2.status', 'left')
        ->assertJsonStructure(['data' => [['user_id', 'user' => ['id', 'username', 'display_name', 'avatar_url'], 'is_host', 'status', 'joined_at', 'left_at']]]);
});

it('includes pass-and-play guests in the roster', function () {
    $host = User::factory()->create(['clerk_user_id' => 'players_host_guest']);
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id, 'joined_at' => now()->subMinutes(2)]);
    PartyMember::factory()->guest()->create(['party_id' => $party->id, 'guest_name' => 'Sam', 'joined_at' => now()->subMinute()]);

    authAsPlayer($host);

    $this->getJson(partyPlayersEndpoint($party))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.1.user_id', null)
        ->assertJsonPath('data.1.guest_name', 'Sam')
        ->assertJsonPath('data.1.is_host', false)
        ->assertJsonPath('data.1.user', null);
});

it('leaves out members whose account was deleted', function () {
    $host = User::factory()->create(['clerk_user_id' => 'players_host_deleted']);
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    $gone = PartyMember::factory()->create(['party_id' => $party->id, 'status' => PartyMemberStatus::Left]);
    $gone->user->delete();

    authAsPlayer($host);

    $this->getJson(partyPlayersEndpoint($party))->assertOk()->assertJsonCount(1, 'data');
});

it('follows party visibility: public parties are visible, private ones only to members', function () {
    $host = User::factory()->create();
    $public = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Public]);
    $private = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Private]);

    authAsPlayer(User::factory()->create(['clerk_user_id' => 'players_outsider']));

    $this->getJson(partyPlayersEndpoint($public))->assertOk();
    $this->getJson(partyPlayersEndpoint($private))->assertStatus(403);
});
