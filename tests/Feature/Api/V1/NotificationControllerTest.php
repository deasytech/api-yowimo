<?php

use App\Models\Notification;
use App\Models\User;
use Tests\Support\FakesClerk;

const API_V1_NOTIFICATIONS_ENDPOINT = '/api/v1/notifications';
const API_V1_NOTIFICATIONS_READ_ENDPOINT = '/api/v1/notifications/read';
const API_V1_NOTIFICATIONS_READ_ALL_ENDPOINT = '/api/v1/notifications/read-all';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

it('rejects notification requests with no bearer token', function () {
    $this->getJson(API_V1_NOTIFICATIONS_ENDPOINT)->assertStatus(401);
    $this->patchJson(API_V1_NOTIFICATIONS_READ_ENDPOINT, ['notification_id' => 1])->assertStatus(401);
    $this->patchJson(API_V1_NOTIFICATIONS_READ_ALL_ENDPOINT)->assertStatus(401);
});

it('lists the authenticated user\'s notifications newest first with pagination meta', function () {
    $token = $this->clerkToken(['sub' => 'user_notif_list']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'user_notif_list')->firstOrFail();

    $older = Notification::factory()->create(['user_id' => $user->id, 'created_at' => now()->subMinute()]);
    $newer = Notification::factory()->create(['user_id' => $user->id, 'created_at' => now()]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_NOTIFICATIONS_ENDPOINT.'?per_page=1')
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(1);
    $response->assertJsonPath('data.0.id', $newer->id);
    $response->assertJsonPath('meta.per_page', 1);
    $response->assertJsonPath('meta.has_more_pages', true);

    $next = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_NOTIFICATIONS_ENDPOINT.'?per_page=1&cursor='.$response->json('meta.next_cursor'))
        ->assertStatus(200);

    $next->assertJsonPath('data.0.id', $older->id);
    $next->assertJsonPath('meta.has_more_pages', false);
});

it('does not include another user\'s notifications in the list', function () {
    $token = $this->clerkToken(['sub' => 'user_notif_scope']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'user_notif_scope')->firstOrFail();

    $other = User::factory()->create();
    Notification::factory()->create(['user_id' => $other->id]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson(API_V1_NOTIFICATIONS_ENDPOINT)
        ->assertStatus(200);

    expect($response->json('data'))->toHaveCount(0);
});

it('marks a single notification as read', function () {
    $token = $this->clerkToken(['sub' => 'user_notif_read']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'user_notif_read')->firstOrFail();
    $notification = Notification::factory()->create(['user_id' => $user->id, 'read_at' => null]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_NOTIFICATIONS_READ_ENDPOINT, ['notification_id' => $notification->id])
        ->assertStatus(200)
        ->assertJsonPath('data.id', $notification->id);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('404s marking a notification read when it belongs to another user', function () {
    $token = $this->clerkToken(['sub' => 'user_notif_read_denied']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    $other = User::factory()->create();
    $notification = Notification::factory()->create(['user_id' => $other->id, 'read_at' => null]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_NOTIFICATIONS_READ_ENDPOINT, ['notification_id' => $notification->id])
        ->assertStatus(404);

    expect($notification->fresh()->read_at)->toBeNull();
});

it('marks all of the authenticated user\'s notifications as read', function () {
    $token = $this->clerkToken(['sub' => 'user_notif_read_all']);

    $this->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();
    $user = User::where('clerk_user_id', 'user_notif_read_all')->firstOrFail();

    $first = Notification::factory()->create(['user_id' => $user->id, 'read_at' => null]);
    $second = Notification::factory()->create(['user_id' => $user->id, 'read_at' => null]);

    $other = User::factory()->create();
    $othersNotification = Notification::factory()->create(['user_id' => $other->id, 'read_at' => null]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->patchJson(API_V1_NOTIFICATIONS_READ_ALL_ENDPOINT)
        ->assertStatus(200);

    expect($first->fresh()->read_at)->not->toBeNull();
    expect($second->fresh()->read_at)->not->toBeNull();
    expect($othersNotification->fresh()->read_at)->toBeNull();
});
