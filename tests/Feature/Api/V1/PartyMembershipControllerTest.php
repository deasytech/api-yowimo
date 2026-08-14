<?php

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

it('rejects join requests with no bearer token', function () {
    $party = Party::factory()->create();

    $this->postJson(joinEndpoint($party))->assertStatus(401);
});

it('joins a joinable party and increments players_count', function () {
    $token = $this->clerkToken(['sub' => 'user_joiner_one']);
    $party = Party::factory()->create([
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'max_players' => 8,
        'players_count' => 1,
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $party->host_id]);

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
    $party = Party::factory()->create([
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'max_players' => 8,
        'players_count' => 1,
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $party->host_id]);

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(joinEndpoint($party))->assertStatus(200);
    $this->withHeader('Authorization', "Bearer {$token}")->postJson(joinEndpoint($party))->assertStatus(200);

    expect($party->fresh()->players_count)->toBe(2);
    expect(PartyMember::where('party_id', $party->id)->count())->toBe(2);
    expect(PartyMember::where('party_id', $party->id)->count())->toBe($party->fresh()->players_count);
});

it('rejects joining a full party', function () {
    $token = $this->clerkToken(['sub' => 'user_joiner_full']);
    $party = Party::factory()->create([
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'max_players' => 2,
        'players_count' => 2,
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $party->host_id]);
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

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(joinEndpoint($party))
        ->assertStatus($status === PartyStatus::Draft ? 403 : 422);

    expect(PartyMember::where('party_id', $party->id)->count())->toBe($party->fresh()->players_count);
})->with([PartyStatus::Ended, PartyStatus::Cancelled, PartyStatus::Draft]);

it('forbids joining a private party the user cannot view', function () {
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

it('leaves a party and decrements players_count', function () {
    $token = $this->clerkToken(['sub' => 'user_leaver']);
    $party = Party::factory()->create([
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'max_players' => 8,
        'players_count' => 1,
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $party->host_id]);

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(joinEndpoint($party))->assertStatus(200);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(leaveEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.players_count', 1)
        ->assertJsonPath('data.joined_by_me', false);

    $user = User::where('clerk_user_id', 'user_leaver')->firstOrFail();
    expect(PartyMember::where('party_id', $party->id)->where('user_id', $user->id)->exists())->toBeFalse();
    expect($party->fresh()->players_count)->toBe(1);
    expect(PartyMember::where('party_id', $party->id)->count())->toBe($party->fresh()->players_count);
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
    $hostToken = $this->clerkToken(['sub' => 'user_host_leave_block']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_host_leave_block')->firstOrFail();

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
    $hostToken = $this->clerkToken(['sub' => 'user_host_start']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_host_start')->firstOrFail();

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
    $hostToken = $this->clerkToken(['sub' => 'user_host_start_live']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_host_start_live')->firstOrFail();

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
    $hostToken = $this->clerkToken(['sub' => 'user_host_end']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_host_end')->firstOrFail();

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
    $hostToken = $this->clerkToken(['sub' => 'user_host_end_draft']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_host_end_draft')->firstOrFail();

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Draft,
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(endEndpoint($party))
        ->assertStatus(422);
});
