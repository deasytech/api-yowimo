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
    config([
        'services.livekit.api_key' => 'lk_test_key',
        'services.livekit.api_secret' => 'lk_test_secret_at_least_32_characters_long',
        'services.livekit.url' => 'wss://example.livekit.cloud',
    ]);
});

function videoTokenEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/video-token";
}

it('rejects video token requests with no bearer token', function () {
    $party = Party::factory()->create(['mode' => PartyMode::Online, 'status' => PartyStatus::Live]);

    $this->postJson(videoTokenEndpoint($party))->assertStatus(401);
});

it('issues a video token to an active member of a live online party', function () {
    $token = $this->clerkToken(['sub' => 'video_member']);
    $party = Party::factory()->create(['mode' => PartyMode::Online, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Public]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'video_member')->firstOrFail();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(videoTokenEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.room', "party-{$party->id}")
        ->assertJsonPath('data.url', 'wss://example.livekit.cloud')
        ->assertJsonStructure(['data' => ['token', 'url', 'room']]);
});

it('issues a video token for a hybrid party too', function () {
    $token = $this->clerkToken(['sub' => 'video_member_hybrid']);
    $party = Party::factory()->create(['mode' => PartyMode::Hybrid, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Public]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'video_member_hybrid')->firstOrFail();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(videoTokenEndpoint($party))
        ->assertStatus(200);
});

it('forbids a video token for an in-person party', function () {
    $token = $this->clerkToken(['sub' => 'video_member_in_person']);
    $party = Party::factory()->create(['mode' => PartyMode::InPerson, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Public]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'video_member_in_person')->firstOrFail();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(videoTokenEndpoint($party))
        ->assertStatus(403);
});

it('forbids a video token before the party is live', function () {
    $token = $this->clerkToken(['sub' => 'video_member_not_live']);
    $party = Party::factory()->create(['mode' => PartyMode::Online, 'status' => PartyStatus::Scheduled, 'visibility' => PartyVisibility::Public]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'video_member_not_live')->firstOrFail();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(videoTokenEndpoint($party))
        ->assertStatus(403);
});

it('forbids a non-member from requesting a video token', function () {
    $token = $this->clerkToken(['sub' => 'video_non_member']);
    $party = Party::factory()->create(['mode' => PartyMode::Online, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Private]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(videoTokenEndpoint($party))
        ->assertStatus(403);
});

it('returns 503 when LiveKit is not configured', function () {
    config(['services.livekit.api_key' => null, 'services.livekit.api_secret' => null]);
    $token = $this->clerkToken(['sub' => 'video_member_not_configured']);
    $party = Party::factory()->create(['mode' => PartyMode::Online, 'status' => PartyStatus::Live, 'visibility' => PartyVisibility::Public]);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'video_member_not_configured')->firstOrFail();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(videoTokenEndpoint($party))
        ->assertStatus(503);
});
