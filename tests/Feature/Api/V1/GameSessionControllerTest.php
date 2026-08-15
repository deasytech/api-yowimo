<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function startGameEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/game/start";
}

function nextTurnEndpoint(int $gameSessionId): string
{
    return "/api/v1/game/{$gameSessionId}/next-turn";
}

function makeLivePartyForController(User $host, int $memberCount = 2): Party
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count(10)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(10)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'pack_id' => $pack->id,
        'status' => PartyStatus::Live,
    ]);

    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    for ($i = 1; $i < $memberCount; $i++) {
        PartyMember::factory()->create(['party_id' => $party->id]);
    }

    return $party;
}

it('rejects starting a game with no bearer token', function () {
    $party = Party::factory()->create();

    $this->postJson(startGameEndpoint($party))->assertStatus(401);
});

it('lets the host start a game session for a live party', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_game_host']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_game_host')->firstOrFail();

    $party = makeLivePartyForController($host, 3);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(startGameEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'running')
        ->assertJsonPath('data.rounds_count', 10)
        ->assertJsonPath('data.current_round_number', 1)
        ->assertJsonPath('data.current_round.number', 1)
        ->assertJsonPath('data.current_turn.position', 0);
});

it('lets the host configure the rounds count when starting', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_game_host_rounds']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_game_host_rounds')->firstOrFail();

    $party = makeLivePartyForController($host);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(startGameEndpoint($party), ['rounds' => 20])
        ->assertStatus(200)
        ->assertJsonPath('data.rounds_count', 20);
});

it('rejects an invalid rounds value', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_game_host_bad_rounds']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_game_host_bad_rounds')->firstOrFail();

    $party = makeLivePartyForController($host);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(startGameEndpoint($party), ['rounds' => 7])
        ->assertStatus(422);
});

it('forbids a non-host from starting a game', function () {
    $host = User::factory()->create();
    $party = makeLivePartyForController($host);

    $token = $this->clerkToken(['sub' => 'user_game_non_host']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(startGameEndpoint($party))
        ->assertStatus(403);
});

it('rejects starting a game twice for the same party', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_game_host_twice']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_game_host_twice')->firstOrFail();

    $party = makeLivePartyForController($host);

    $this->withHeader('Authorization', "Bearer {$hostToken}")->postJson(startGameEndpoint($party))->assertStatus(200);
    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(startGameEndpoint($party))
        ->assertStatus(409);
});

it('lets the host advance to the next turn', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_game_host_advance']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_game_host_advance')->firstOrFail();

    $party = makeLivePartyForController($host, 3);

    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(startGameEndpoint($party))
        ->assertStatus(200);

    $sessionId = $start->json('data.id');

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(nextTurnEndpoint($sessionId))
        ->assertStatus(200)
        ->assertJsonPath('data.current_turn.position', 1);
});

it('forbids a non-host from advancing the turn', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_game_host_advance_forbid']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_game_host_advance_forbid')->firstOrFail();

    $party = makeLivePartyForController($host, 2);

    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(startGameEndpoint($party))
        ->assertStatus(200);

    $token = $this->clerkToken(['sub' => 'user_game_non_host_advance']);

    // The `clerk` guard caches its resolved user for the lifetime of the app
    // instance; force it to re-resolve so the non-host's token is actually used.
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(nextTurnEndpoint($start->json('data.id')))
        ->assertStatus(403);
});

it('rejects advancing a completed session', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_game_host_completed']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_game_host_completed')->firstOrFail();

    $party = makeLivePartyForController($host, 1);

    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(startGameEndpoint($party), ['rounds' => 5])
        ->assertStatus(200);

    $sessionId = $start->json('data.id');

    for ($i = 0; $i < 4; $i++) {
        $this->withHeader('Authorization', "Bearer {$hostToken}")
            ->postJson(nextTurnEndpoint($sessionId))
            ->assertStatus(200);
    }

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(nextTurnEndpoint($sessionId))
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'completed');

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(nextTurnEndpoint($sessionId))
        ->assertStatus(422);
});
