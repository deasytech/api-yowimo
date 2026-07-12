<?php

use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\GameType;
use App\Models\Party;
use App\Models\User;
use Tests\Support\FakesClerk;
use Tests\TestCase;

const API_V1_PARTIES_ENDPOINT = '/api/v1/parties';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function provisionUserFromToken(TestCase $test, string $token, string $sub): User
{
    $test->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    return User::where('clerk_user_id', $sub)->firstOrFail();
}

it('rejects requests with no bearer token', function () {
    $this->getJson(API_V1_PARTIES_ENDPOINT)->assertStatus(401);
    $this->postJson(API_V1_PARTIES_ENDPOINT, [])->assertStatus(401);
});

it('only lists public, discoverable parties', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_list_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_list_host');

    Party::factory()->create(['host_id' => $host->id, 'visibility' => PartyVisibility::Public, 'status' => PartyStatus::Live, 'title' => 'Public Live']);
    Party::factory()->create(['host_id' => $host->id, 'visibility' => PartyVisibility::Public, 'status' => PartyStatus::Scheduled, 'title' => 'Public Scheduled']);
    Party::factory()->create(['host_id' => $host->id, 'visibility' => PartyVisibility::Private, 'status' => PartyStatus::Live, 'title' => 'Private Live']);
    Party::factory()->create(['host_id' => $host->id, 'visibility' => PartyVisibility::Public, 'status' => PartyStatus::Draft, 'title' => 'Public Draft']);

    $response = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT)
        ->assertStatus(200);

    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain('Public Live')
        ->toContain('Public Scheduled')
        ->not->toContain('Private Live')
        ->not->toContain('Public Draft');
});

it('filters the discover feed by mode', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_mode_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_mode_host');

    Party::factory()->create(['host_id' => $host->id, 'visibility' => PartyVisibility::Public, 'status' => PartyStatus::Live, 'mode' => PartyMode::Online, 'title' => 'Online Party']);
    Party::factory()->create(['host_id' => $host->id, 'visibility' => PartyVisibility::Public, 'status' => PartyStatus::Live, 'mode' => PartyMode::InPerson, 'title' => 'In Person Party']);

    $response = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'?mode=online')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data');

    $response->assertJsonPath('data.0.title', 'Online Party');
});

it('searches the discover feed by title', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_search_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_search_host');

    Party::factory()->create(['host_id' => $host->id, 'visibility' => PartyVisibility::Public, 'status' => PartyStatus::Live, 'title' => 'Friday Night Chaos']);
    Party::factory()->create(['host_id' => $host->id, 'visibility' => PartyVisibility::Public, 'status' => PartyStatus::Live, 'title' => 'Sunday Brunch']);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'?search=Chaos')
        ->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.title', 'Friday Night Chaos');
});

it('creates an online party with a generated room code and live status', function () {
    $token = $this->clerkToken(['sub' => 'user_create_online']);
    $gameType = GameType::factory()->create();

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PARTIES_ENDPOINT, [
            'title' => 'My New Party',
            'game_type_id' => $gameType->id,
            'mode' => 'online',
            'visibility' => 'public',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.title', 'My New Party')
        ->assertJsonPath('data.mode', 'online')
        ->assertJsonPath('data.status', 'live');

    $roomCode = $response->json('data.room_code');
    expect($roomCode)->toMatch('/^[A-Z2-9]{6}$/');

    $host = User::where('clerk_user_id', 'user_create_online')->firstOrFail();
    expect(Party::where('room_code', $roomCode)->first()->host_id)->toBe($host->id);
});

it('creates a draft party when save_as_draft is true', function () {
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PARTIES_ENDPOINT, [
            'title' => 'Draft Party',
            'mode' => 'online',
            'visibility' => 'private',
            'save_as_draft' => true,
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.status', 'draft');
});

it('creates a scheduled party when starts_at is in the future', function () {
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PARTIES_ENDPOINT, [
            'title' => 'Scheduled Party',
            'mode' => 'online',
            'visibility' => 'public',
            'starts_at' => now()->addDay()->toIso8601String(),
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.status', 'scheduled');
});

it('requires location details for in-person parties', function () {
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PARTIES_ENDPOINT, [
            'title' => 'In Person Party',
            'mode' => 'in_person',
            'visibility' => 'public',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('location');
});

it('rejects party creation with validation errors', function () {
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PARTIES_ENDPOINT, [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['title', 'mode', 'visibility']);
});

it('allows anyone to view a public party', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_public_party_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_public_party_host');
    $viewerToken = $this->clerkToken(['sub' => 'user_public_party_viewer']);

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
        'title' => 'Open To All',
    ]);

    // The `clerk` guard caches its resolved user for the lifetime of the app
    // instance; force it to re-resolve so the viewer's token is actually used.
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$viewerToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT."/{$party->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.title', 'Open To All')
        ->assertJsonPath('data.room_code', $party->room_code);
});

it('allows the host to view their own private party', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_private_party_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_private_party_host');

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'status' => PartyStatus::Draft,
    ]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT."/{$party->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $party->id);
});

it('forbids non-hosts from viewing a private party', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_blocked_party_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_blocked_party_host');
    $viewerToken = $this->clerkToken(['sub' => 'user_blocked_party_viewer']);

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'status' => PartyStatus::Scheduled,
    ]);

    // The `clerk` guard caches its resolved user for the lifetime of the app
    // instance; force it to re-resolve so the viewer's token is actually used.
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$viewerToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT."/{$party->id}")
        ->assertStatus(403);
});

it('returns 404 for a party that does not exist', function () {
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'/999999')
        ->assertStatus(404);
});
