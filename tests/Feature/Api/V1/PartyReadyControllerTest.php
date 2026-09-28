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

function readyEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/ready";
}

it('rejects ready requests with no bearer token', function () {
    $party = Party::factory()->create();

    $this->postJson(readyEndpoint($party))->assertStatus(401);
});

it('lets an active member mark themselves ready', function () {
    $token = $this->clerkToken(['sub' => 'party_ready_member']);
    $party = Party::factory()->create(['visibility' => PartyVisibility::Public, 'status' => PartyStatus::Live]);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'party_ready_member')->firstOrFail();
    $membership = PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(readyEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.is_ready', true)
        ->assertJsonPath('data.user_id', $user->id);

    expect($membership->fresh()->is_ready)->toBeTrue();
});

it('lets an active member mark themselves not ready again', function () {
    $token = $this->clerkToken(['sub' => 'party_unready_member']);
    $party = Party::factory()->create(['visibility' => PartyVisibility::Public, 'status' => PartyStatus::Live]);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'party_unready_member')->firstOrFail();
    $membership = PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $user->id, 'is_ready' => true]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(readyEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.is_ready', false);

    expect($membership->fresh()->is_ready)->toBeFalse();
});

it('forbids a non-member from toggling ready', function () {
    $hostToken = $this->clerkToken(['sub' => 'party_ready_host']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'party_ready_host')->firstOrFail();

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'status' => PartyStatus::Live,
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $viewerToken = $this->clerkToken(['sub' => 'party_ready_non_member']);
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$viewerToken}")
        ->postJson(readyEndpoint($party))
        ->assertStatus(403);
});

it('does not gate starting the party on member readiness', function () {
    $hostToken = $this->clerkToken(['sub' => 'party_ready_start_host']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'party_ready_start_host')->firstOrFail();

    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Scheduled]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id, 'is_ready' => false]);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/parties/{$party->id}/start")
        ->assertStatus(200)
        ->assertJsonPath('data.status', 'live');
});
