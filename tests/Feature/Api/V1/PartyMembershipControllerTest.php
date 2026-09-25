<?php

use App\Enums\PartyMemberStatus;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Tests\Support\FakesClerk;
use Tests\TestCase;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function joinEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/join";
}

function leaveEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/leave";
}

function startEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/start";
}

function endEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/end";
}

function cancelEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/cancel";
}

/**
 * Provisions (via a real request, matching this app's auto-provision-on-first-
 * request behavior) and returns [User, bearer token] for a Clerk-authenticated
 * host. clerkToken() is protected and unreachable from this free function, so
 * callers must generate the token themselves and pass it in.
 *
 * @return array{0: User, 1: string}
 */
function provisionPartyHost(TestCase $test, string $token, string $sub): array
{
    $test->withHeader('Authorization', "Bearer {$token}")->getJson(API_V1_ME_ENDPOINT)->assertOk();

    return [User::where('clerk_user_id', $sub)->firstOrFail(), $token];
}

function makeLivePartyWithHostMember(int $maxPlayers = 8, int $playersCount = 1): Party
{
    $party = Party::factory()->create([
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'max_players' => $maxPlayers,
        'players_count' => $playersCount,
    ]);

    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $party->host_id]);

    return $party;
}

it('rejects join requests with no bearer token', function () {
    $party = Party::factory()->create();

    $this->postJson(joinEndpoint($party))->assertStatus(401);
});

it('joins a joinable party and increments players_count', function () {
    $token = $this->clerkToken(['sub' => 'user_joiner_one']);
    $party = makeLivePartyWithHostMember();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(joinEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.players_count', 2)
        ->assertJsonPath('data.joined_by_me', true);

    $user = User::where('clerk_user_id', 'user_joiner_one')->firstOrFail();
    expect(PartyMember::where('party_id', $party->id)->where('user_id', $user->id)->exists())->toBeTrue();
    expect($party->fresh()->players_count)->toBe(2);
    expect(PartyMember::where('party_id', $party->id)->count())->toBe($party->fresh()->players_count);
});

it('does not double count a join from the same user', function () {
    $token = $this->clerkToken(['sub' => 'user_joiner_two']);
    $party = makeLivePartyWithHostMember();

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(joinEndpoint($party))->assertStatus(200);
    $this->withHeader('Authorization', "Bearer {$token}")->postJson(joinEndpoint($party))->assertStatus(200);

    expect($party->fresh()->players_count)->toBe(2);
    expect(PartyMember::where('party_id', $party->id)->count())->toBe(2);
    expect(PartyMember::where('party_id', $party->id)->count())->toBe($party->fresh()->players_count);
});

it('rejects joining a full party', function () {
    $token = $this->clerkToken(['sub' => 'user_joiner_full']);
    $party = makeLivePartyWithHostMember(maxPlayers: 2, playersCount: 2);
    PartyMember::factory()->create(['party_id' => $party->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(joinEndpoint($party))
        ->assertStatus(409);

    expect($party->fresh()->players_count)->toBe(2);
    expect(PartyMember::where('party_id', $party->id)->count())->toBe($party->fresh()->players_count);
});

it('rejects joining a party that is not in a joinable status', function (PartyStatus $status) {
    $token = $this->clerkToken(['sub' => 'user_joiner_'.$status->value]);
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => $status,
        'max_players' => 8,
        'players_count' => 1,
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    // A public party is always joinable-in-principle (join()'s policy check
    // passes); it's the party's current status that rejects the attempt
    // here, uniformly across every non-joinable status.
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(joinEndpoint($party))
        ->assertStatus(422);

    expect(PartyMember::where('party_id', $party->id)->count())->toBe($party->fresh()->players_count);
})->with([PartyStatus::Ended, PartyStatus::Cancelled, PartyStatus::Draft]);

it('forbids joining a private party with no room code', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'status' => PartyStatus::Live,
        'players_count' => 1,
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $token = $this->clerkToken(['sub' => 'user_join_private_viewer']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(joinEndpoint($party))
        ->assertStatus(403);

    expect(PartyMember::where('party_id', $party->id)->count())->toBe($party->fresh()->players_count);
});

it('forbids joining a private party with the wrong room code', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'status' => PartyStatus::Live,
        'players_count' => 1,
        'room_code' => 'RIGHT1',
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $token = $this->clerkToken(['sub' => 'user_join_private_wrong_code']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(joinEndpoint($party), ['room_code' => 'WRONG1'])
        ->assertStatus(403);
});

it('lets a user join a private party with the correct room code', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'status' => PartyStatus::Live,
        'players_count' => 1,
        'room_code' => 'INVITE',
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $token = $this->clerkToken(['sub' => 'user_join_private_invited']);

    // Case-insensitive, matching the room-code lookup endpoint's behavior.
    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(joinEndpoint($party), ['room_code' => 'invite'])
        ->assertStatus(200)
        ->assertJsonPath('data.joined_by_me', true);
});

it('reuses the same membership row on rejoin after leaving, preserving history', function () {
    $token = $this->clerkToken(['sub' => 'user_rejoiner']);
    $party = makeLivePartyWithHostMember();

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(joinEndpoint($party))->assertStatus(200);
    $this->withHeader('Authorization', "Bearer {$token}")->deleteJson(leaveEndpoint($party))->assertStatus(200);

    $user = User::where('clerk_user_id', 'user_rejoiner')->firstOrFail();
    $membership = PartyMember::where('party_id', $party->id)->where('user_id', $user->id)->firstOrFail();
    expect($membership->status)->toBe(PartyMemberStatus::Left);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(joinEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.joined_by_me', true);

    expect(PartyMember::where('party_id', $party->id)->where('user_id', $user->id)->count())->toBe(1);
    $membership->refresh();
    expect($membership->status)->toBe(PartyMemberStatus::Active);
    expect($membership->left_at)->toBeNull();
    expect($party->fresh()->players_count)->toBe(2);
});

it('leaves a party and decrements players_count', function () {
    $token = $this->clerkToken(['sub' => 'user_leaver']);
    $party = makeLivePartyWithHostMember();

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(joinEndpoint($party))->assertStatus(200);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(leaveEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.players_count', 1)
        ->assertJsonPath('data.joined_by_me', false);

    // The membership row survives as history (status: left), not deleted —
    // only the active-membership count/roster shrinks.
    $user = User::where('clerk_user_id', 'user_leaver')->firstOrFail();
    $membership = PartyMember::where('party_id', $party->id)->where('user_id', $user->id)->firstOrFail();
    expect($membership->status)->toBe(PartyMemberStatus::Left);
    expect($membership->left_at)->not->toBeNull();
    expect($party->fresh()->players_count)->toBe(1);
});

it('does not go below zero when leaving without having joined', function () {
    $token = $this->clerkToken(['sub' => 'user_leaver_none']);
    $party = Party::factory()->create([
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'players_count' => 0,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(leaveEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.players_count', 0);

    expect($party->fresh()->players_count)->toBe(0);
});

it('blocks the host from leaving their own party', function () {
    [$host, $hostToken] = provisionPartyHost($this, $this->clerkToken(['sub' => 'user_host_leave_block']), 'user_host_leave_block');

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'players_count' => 1,
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->deleteJson(leaveEndpoint($party))
        ->assertStatus(409);

    expect($party->fresh()->players_count)->toBe(1);
    expect(PartyMember::where('party_id', $party->id)->count())->toBe($party->fresh()->players_count);
});

it('lets the host start their draft party', function () {
    [$host, $hostToken] = provisionPartyHost($this, $this->clerkToken(['sub' => 'user_host_start']), 'user_host_start');

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Draft,
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(startEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'live');

    expect($party->fresh()->status)->toBe(PartyStatus::Live);
});

it('forbids a non-host from starting a party', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Draft,
    ]);

    $token = $this->clerkToken(['sub' => 'user_non_host_start']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(startEndpoint($party))
        ->assertStatus(403);

    expect($party->fresh()->status)->toBe(PartyStatus::Draft);
});

it('rejects starting a party that is already live', function () {
    [$host, $hostToken] = provisionPartyHost($this, $this->clerkToken(['sub' => 'user_host_start_live']), 'user_host_start_live');

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(startEndpoint($party))
        ->assertStatus(422);
});

it('lets the host end their live party', function () {
    [$host, $hostToken] = provisionPartyHost($this, $this->clerkToken(['sub' => 'user_host_end']), 'user_host_end');

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(endEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'ended');

    expect($party->fresh()->status)->toBe(PartyStatus::Ended);
});

it('forbids a non-host from ending a party', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
    ]);

    $token = $this->clerkToken(['sub' => 'user_non_host_end']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(endEndpoint($party))
        ->assertStatus(403);

    expect($party->fresh()->status)->toBe(PartyStatus::Live);
});

it('rejects ending a party that is not live', function () {
    [$host, $hostToken] = provisionPartyHost($this, $this->clerkToken(['sub' => 'user_host_end_draft']), 'user_host_end_draft');

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Draft,
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(endEndpoint($party))
        ->assertStatus(422);
});

it('lets the host cancel their draft party', function (PartyStatus $status) {
    [$host, $hostToken] = provisionPartyHost($this, $this->clerkToken(['sub' => 'user_host_cancel_'.$status->value]), 'user_host_cancel_'.$status->value);

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => $status,
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(cancelEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'cancelled');

    expect($party->fresh()->status)->toBe(PartyStatus::Cancelled);
})->with([PartyStatus::Draft, PartyStatus::Scheduled]);

it('forbids a non-host from cancelling a party', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Draft,
    ]);

    $token = $this->clerkToken(['sub' => 'user_non_host_cancel']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(cancelEndpoint($party))
        ->assertStatus(403);

    expect($party->fresh()->status)->toBe(PartyStatus::Draft);
});

it('rejects cancelling a party that is already live', function () {
    [$host, $hostToken] = provisionPartyHost($this, $this->clerkToken(['sub' => 'user_host_cancel_live']), 'user_host_cancel_live');

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(cancelEndpoint($party))
        ->assertStatus(422);

    expect($party->fresh()->status)->toBe(PartyStatus::Live);
});
