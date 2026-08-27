<?php

use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\PushToken;
use App\Models\TokenBundle;
use App\Models\User;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('throttles the purchases limiter per user and does not affect other users', function () {
    $bundle = TokenBundle::factory()->create(['tokens' => 10]);

    $token = $this->clerkToken(['sub' => 'user_throttle_purchases']);
    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    for ($i = 0; $i < 10; $i++) {
        $this->withHeader('Authorization', "Bearer {$token}")
            ->withHeader('Idempotency-Key', "purchase_throttle_{$i}")
            ->postJson("/api/v1/token-bundles/{$bundle->id}/purchase")
            ->assertStatus(201);
    }

    $this->withHeader('Authorization', "Bearer {$token}")
        ->withHeader('Idempotency-Key', 'purchase_throttle_overflow')
        ->postJson("/api/v1/token-bundles/{$bundle->id}/purchase")
        ->assertStatus(429);

    $otherToken = $this->clerkToken(['sub' => 'user_throttle_purchases_other']);
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$otherToken}")
        ->withHeader('Idempotency-Key', 'purchase_throttle_other')
        ->postJson("/api/v1/token-bundles/{$bundle->id}/purchase")
        ->assertStatus(201);
});

it('throttles the push-tokens limiter per user', function () {
    $token = $this->clerkToken(['sub' => 'user_throttle_push']);

    for ($i = 0; $i < 10; $i++) {
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v1/push-tokens', ['token' => "device-throttle-{$i}", 'platform' => 'ios'])
            ->assertStatus(200);
    }

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/push-tokens', ['token' => 'device-throttle-overflow', 'platform' => 'ios'])
        ->assertStatus(429);

    $user = User::where('clerk_user_id', 'user_throttle_push')->firstOrFail();
    expect(PushToken::where('user_id', $user->id)->count())->toBe(1);
});

it('throttles the party-actions limiter per user', function () {
    $hostToken = $this->clerkToken(['sub' => 'user_throttle_party_host']);
    $this->withHeader('Authorization', "Bearer {$hostToken}")->getJson('/api/v1/users/me')->assertOk();
    $host = User::where('clerk_user_id', 'user_throttle_party_host')->firstOrFail();

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'visibility' => PartyVisibility::Public,
        'status' => PartyStatus::Live,
    ]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $liker = $this->clerkToken(['sub' => 'user_throttle_party_liker']);
    $this->app->make('auth')->forgetGuards();

    for ($i = 0; $i < 30; $i++) {
        $this->withHeader('Authorization', "Bearer {$liker}")
            ->postJson("/api/v1/parties/{$party->id}/like")
            ->assertStatus(200);
    }

    $this->withHeader('Authorization', "Bearer {$liker}")
        ->postJson("/api/v1/parties/{$party->id}/like")
        ->assertStatus(429);
});

it('throttles the friend-requests limiter per user', function () {
    $sender = $this->clerkToken(['sub' => 'user_throttle_friend_sender']);
    $this->withHeader('Authorization', "Bearer {$sender}")->getJson('/api/v1/users/me')->assertOk();

    $receivers = User::factory()->count(21)->create();

    foreach ($receivers->take(20) as $receiver) {
        $this->withHeader('Authorization', "Bearer {$sender}")
            ->postJson('/api/v1/friend-requests', ['receiver_id' => $receiver->id])
            ->assertStatus(201);
    }

    $this->withHeader('Authorization', "Bearer {$sender}")
        ->postJson('/api/v1/friend-requests', ['receiver_id' => $receivers->last()->id])
        ->assertStatus(429);
});
