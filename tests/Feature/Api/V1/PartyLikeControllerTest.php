<?php

use App\Enums\PartyVisibility;
use App\Models\Party;
use App\Models\PartyLike;
use App\Models\User;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function likeEndpoint(Party $party): string
{
    return "/api/v1/parties/{$party->id}/like";
}

it('rejects like requests with no bearer token', function () {
    $party = Party::factory()->create();

    $this->postJson(likeEndpoint($party))->assertStatus(401);
});

it('likes a party and increments the like count', function () {
    $token = $this->clerkToken(['sub' => 'user_liker_one']);
    $party = Party::factory()->create(['visibility' => PartyVisibility::Public, 'likes_count' => 0]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson(likeEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.likes_count', 1)
        ->assertJsonPath('data.liked_by_me', true);

    $user = User::where('clerk_user_id', 'user_liker_one')->firstOrFail();
    expect(PartyLike::where('party_id', $party->id)->where('user_id', $user->id)->exists())->toBeTrue();
    expect($party->fresh()->likes_count)->toBe(1);
});

it('does not double count a like from the same user', function () {
    $token = $this->clerkToken(['sub' => 'user_liker_two']);
    $party = Party::factory()->create(['visibility' => PartyVisibility::Public, 'likes_count' => 0]);

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(likeEndpoint($party))->assertStatus(200);
    $this->withHeader('Authorization', "Bearer {$token}")->postJson(likeEndpoint($party))->assertStatus(200);

    expect($party->fresh()->likes_count)->toBe(1);
});

it('unlikes a party and decrements the like count', function () {
    $token = $this->clerkToken(['sub' => 'user_unliker']);
    $party = Party::factory()->create(['visibility' => PartyVisibility::Public, 'likes_count' => 0]);

    $this->withHeader('Authorization', "Bearer {$token}")->postJson(likeEndpoint($party))->assertStatus(200);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(likeEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.likes_count', 0)
        ->assertJsonPath('data.liked_by_me', false);

    expect($party->fresh()->likes_count)->toBe(0);
});

it('does not go below zero when unliking without a prior like', function () {
    $token = $this->clerkToken(['sub' => 'user_unliker_none']);
    $party = Party::factory()->create(['visibility' => PartyVisibility::Public, 'likes_count' => 0]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson(likeEndpoint($party))
        ->assertStatus(200)
        ->assertJsonPath('data.likes_count', 0);

    expect($party->fresh()->likes_count)->toBe(0);
});

it('forbids liking a private party the user cannot view', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_like_private_host']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_like_private_host')->firstOrFail();

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'likes_count' => 0,
    ]);

    $viewerToken = $this->clerkToken(['sub' => 'user_like_private_viewer']);

    // The `clerk` guard caches its resolved user for the lifetime of the app
    // instance; force it to re-resolve so the viewer's token is actually used.
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$viewerToken}")
        ->postJson(likeEndpoint($party))
        ->assertStatus(403);

    expect($party->fresh()->likes_count)->toBe(0);
});

it('forbids unliking a private party the user cannot view', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_unlike_private_host']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_unlike_private_host')->firstOrFail();

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Private,
        'likes_count' => 0,
    ]);

    $viewerToken = $this->clerkToken(['sub' => 'user_unlike_private_viewer']);

    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$viewerToken}")
        ->deleteJson(likeEndpoint($party))
        ->assertStatus(403);
});
