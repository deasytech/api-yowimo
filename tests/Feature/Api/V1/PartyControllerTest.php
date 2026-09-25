<?php

use App\Enums\PartyMode;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\GameSession;
use App\Models\GameType;
use App\Models\Pack;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

it('creates a party with an uploaded cover image that appears in the discover feed', function () {
    Storage::fake('public');
    $token = $this->clerkToken(['sub' => 'user_create_with_image']);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->post(API_V1_PARTIES_ENDPOINT, [
            'title' => 'Party With A Cover',
            'mode' => 'online',
            'visibility' => 'public',
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ])
        ->assertStatus(201);

    $coverImageUrl = $response->json('data.cover_image_url');
    expect($coverImageUrl)->not->toBeNull();

    $party = Party::where('title', 'Party With A Cover')->firstOrFail();
    expect($party->cover_image_url)->toBe($coverImageUrl);

    Storage::disk('public')->assertExists(
        str($coverImageUrl)->after('/storage/')->toString()
    );

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PARTIES_ENDPOINT)
        ->assertStatus(200)
        ->assertJsonPath('data.0.cover_image_url', $coverImageUrl);
});

it('rejects a non-image file as the cover image', function () {
    Storage::fake('public');
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->post(API_V1_PARTIES_ENDPOINT, [
            'title' => 'Party With A Bad Cover',
            'mode' => 'online',
            'visibility' => 'public',
            'cover_image' => UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml'),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('cover_image');
});

it('creates a party with no cover image when none is provided', function () {
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(API_V1_PARTIES_ENDPOINT, [
            'title' => 'Party With No Cover',
            'mode' => 'online',
            'visibility' => 'public',
        ])
        ->assertStatus(201)
        ->assertJsonPath('data.cover_image_url', null);
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

it('forbids non-hosts from viewing a draft party even when it is marked public', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_draft_public_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_draft_public_host');
    $viewerToken = $this->clerkToken(['sub' => 'user_draft_public_viewer']);

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Draft,
    ]);

    // The `clerk` guard caches its resolved user for the lifetime of the app
    // instance; force it to re-resolve so the viewer's token is actually used.
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$viewerToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT."/{$party->id}")
        ->assertStatus(403);

    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT."/{$party->id}")
        ->assertStatus(200);
});

it('returns 404 for a party that does not exist', function () {
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'/999999')
        ->assertStatus(404);
});

it('resolves a room code to its party, including a private one', function () {
    // room_code itself is conditionally hidden from the response for a
    // non-host viewer of a private party (PartyResource's existing rule) —
    // the point being tested here is that the *lookup* isn't blocked by
    // that same visibility rule, not that the code is echoed back.
    $token = $this->clerkToken();
    $party = Party::factory()->create([
        'visibility' => PartyVisibility::Private,
        'status' => PartyStatus::Live,
        'room_code' => 'ABC234',
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'/lookup?room_code=ABC234')
        ->assertStatus(200)
        ->assertJsonPath('data.id', $party->id)
        ->assertJsonPath('data.title', $party->title);
});

it('resolves a room code case-insensitively', function () {
    $token = $this->clerkToken();
    $party = Party::factory()->create(['status' => PartyStatus::Live, 'room_code' => 'XYZ789']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'/lookup?room_code=xyz789')
        ->assertStatus(200)
        ->assertJsonPath('data.id', $party->id);
});

it('returns 404 for a room code that does not exist', function () {
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'/lookup?room_code=NOPE00')
        ->assertStatus(404);
});

it('returns 404 for a room code belonging to a draft party', function () {
    $token = $this->clerkToken();
    Party::factory()->create(['status' => PartyStatus::Draft, 'room_code' => 'DRAFT1']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'/lookup?room_code=DRAFT1')
        ->assertStatus(404);
});

it('returns 404 for a room code belonging to an ended party', function () {
    $token = $this->clerkToken();
    Party::factory()->create(['status' => PartyStatus::Ended, 'room_code' => 'ENDED1']);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'/lookup?room_code=ENDED1')
        ->assertStatus(404);
});

it('rejects a room code lookup with no room_code given', function () {
    $token = $this->clerkToken();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_PARTIES_ENDPOINT.'/lookup')
        ->assertStatus(422)
        ->assertJsonValidationErrors('room_code');
});

it('lets the host set the game type and pack after creating the party without one', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_update_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_update_host');
    $party = Party::factory()->create(['host_id' => $host->id, 'game_type_id' => null, 'pack_id' => null]);

    $gameType = GameType::factory()->create();
    $pack = Pack::factory()->create(['game_type_id' => $gameType->id]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->patchJson(API_V1_PARTIES_ENDPOINT."/{$party->id}", [
            'game_type_id' => $gameType->id,
            'pack_id' => $pack->id,
        ])
        ->assertStatus(200)
        ->assertJsonPath('data.game_type.id', $gameType->id)
        ->assertJsonPath('data.pack.id', $pack->id);

    expect($party->fresh()->pack_id)->toBe($pack->id);
});

it('lets the host clear the pack selection by setting it back to null', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_update_clear_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_update_clear_host');
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => Pack::factory()->create()->id]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->patchJson(API_V1_PARTIES_ENDPOINT."/{$party->id}", ['pack_id' => null])
        ->assertStatus(200)
        ->assertJsonPath('data.pack', null);

    expect($party->fresh()->pack_id)->toBeNull();
});

it('forbids a non-host from updating the party', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_update_forbidden_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_update_forbidden_host');
    $party = Party::factory()->create(['host_id' => $host->id]);

    $otherToken = $this->clerkToken(['sub' => 'user_update_forbidden_other']);
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$otherToken}")
        ->patchJson(API_V1_PARTIES_ENDPOINT."/{$party->id}", ['pack_id' => Pack::factory()->create()->id])
        ->assertStatus(403);
});

it('rejects changing the pack once a game session has already started for the party', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_update_started_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_update_started_host');
    $party = Party::factory()->create(['host_id' => $host->id]);
    GameSession::factory()->create(['party_id' => $party->id]);

    $newPack = Pack::factory()->create();

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->patchJson(API_V1_PARTIES_ENDPOINT."/{$party->id}", ['pack_id' => $newPack->id])
        ->assertStatus(409);

    expect($party->fresh()->pack_id)->not->toBe($newPack->id);
});

it('allows updating unrelated fields even after a game session has started', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_update_started_noop_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_update_started_noop_host');
    $party = Party::factory()->create(['host_id' => $host->id]);
    GameSession::factory()->create(['party_id' => $party->id]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->patchJson(API_V1_PARTIES_ENDPOINT."/{$party->id}", [])
        ->assertStatus(200);
});

it('rejects an invalid pack id when updating a party', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_update_invalid_pack_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_update_invalid_pack_host');
    $party = Party::factory()->create(['host_id' => $host->id]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->patchJson(API_V1_PARTIES_ENDPOINT."/{$party->id}", ['pack_id' => 999999])
        ->assertStatus(422)
        ->assertJsonValidationErrors('pack_id');
});

it('lets an active member view a private party they belong to', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_private_view_host']);
    $host = provisionUserFromToken($this, $hostToken, 'user_private_view_host');
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'status' => PartyStatus::Live,
    ]);

    $this->app->make('auth')->forgetGuards();
    $memberToken = $this->clerkToken(['sub' => 'user_private_view_member']);
    $member = provisionUserFromToken($this, $memberToken, 'user_private_view_member');
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $member->id]);

    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$memberToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT."/{$party->id}")
        ->assertStatus(200)
        ->assertJsonPath('data.id', $party->id);
});

it('forbids a former member from viewing a private party after leaving', function () {
    $host = User::factory()->create();
    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'status' => PartyStatus::Live,
    ]);

    $formerMemberToken = $this->clerkToken(['sub' => 'user_private_view_former_member']);
    $formerMember = provisionUserFromToken($this, $formerMemberToken, 'user_private_view_former_member');
    PartyMember::factory()->left()->create(['party_id' => $party->id, 'user_id' => $formerMember->id]);

    $this->withHeader('Authorization', "Bearer {$formerMemberToken}")
        ->getJson(API_V1_PARTIES_ENDPOINT."/{$party->id}")
        ->assertStatus(403);
});

it('lists every party the caller hosts, any status, filterable by status', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_hosted_list']);
    $host = provisionUserFromToken($this, $hostToken, 'user_hosted_list');

    Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Draft, 'title' => 'My Draft', 'visibility' => PartyVisibility::Private]);
    Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Ended, 'title' => 'My Ended']);
    $otherHost = User::factory()->create();
    Party::factory()->create(['host_id' => $otherHost->id, 'status' => PartyStatus::Draft, 'title' => 'Not Mine']);

    $response = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->getJson('/api/v1/users/me/parties/hosted')
        ->assertStatus(200);

    $titles = collect($response->json('data'))->pluck('title');
    expect($titles)->toContain('My Draft')->toContain('My Ended')->not->toContain('Not Mine');

    $filtered = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->getJson('/api/v1/users/me/parties/hosted?status=draft')
        ->assertStatus(200);

    expect($filtered->json('data'))->toHaveCount(1);
    $filtered->assertJsonPath('data.0.title', 'My Draft');
});

it('rejects requests to the hosted parties endpoint with no bearer token', function () {
    $this->getJson('/api/v1/users/me/parties/hosted')->assertStatus(401);
});

it('lists every party the caller has ever joined, current and past, excluding self-hosted parties', function () {
    $token = $this->clerkToken(['sub' => 'user_joined_list']);
    $user = provisionUserFromToken($this, $token, 'user_joined_list');

    $activeParty = Party::factory()->create(['title' => 'Still In This One']);
    PartyMember::factory()->create(['party_id' => $activeParty->id, 'user_id' => $user->id]);

    $leftParty = Party::factory()->create(['title' => 'Left This One']);
    PartyMember::factory()->left()->create(['party_id' => $leftParty->id, 'user_id' => $user->id]);

    $ownParty = Party::factory()->create(['host_id' => $user->id, 'title' => 'My Own Party']);
    PartyMember::factory()->create(['party_id' => $ownParty->id, 'user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/users/me/parties/joined')
        ->assertStatus(200);

    $entries = collect($response->json('data'));
    $titles = $entries->pluck('party.title');

    expect($titles)->toContain('Still In This One')->toContain('Left This One')->not->toContain('My Own Party');

    $activeEntry = $entries->firstWhere('party.title', 'Still In This One');
    expect($activeEntry['membership_status'])->toBe('active');
    expect($activeEntry['left_at'])->toBeNull();

    $leftEntry = $entries->firstWhere('party.title', 'Left This One');
    expect($leftEntry['membership_status'])->toBe('left');
    expect($leftEntry['left_at'])->not->toBeNull();
});

it('filters the joined parties list by membership_status', function () {
    $token = $this->clerkToken(['sub' => 'user_joined_filter']);
    $user = provisionUserFromToken($this, $token, 'user_joined_filter');

    $activeParty = Party::factory()->create(['title' => 'Active Membership']);
    PartyMember::factory()->create(['party_id' => $activeParty->id, 'user_id' => $user->id]);

    $leftParty = Party::factory()->create(['title' => 'Left Membership']);
    PartyMember::factory()->left()->create(['party_id' => $leftParty->id, 'user_id' => $user->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/users/me/parties/joined?membership_status=left')
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.party.title', 'Left Membership');
});

it('rejects requests to the joined parties endpoint with no bearer token', function () {
    $this->getJson('/api/v1/users/me/parties/joined')->assertStatus(401);
});
