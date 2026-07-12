<?php

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\WebhookEvent;
use Tests\Support\FakesClerkWebhook;

uses(FakesClerkWebhook::class);

beforeEach(function () {
    $this->fakeClerkWebhookSecret();
});

function clerkUserPayload(string $type, array $data = []): array
{
    return [
        'type' => $type,
        'data' => array_merge([
            'id' => 'user_webhook_1',
            'username' => 'websurfer',
            'first_name' => 'Wanjiru',
            'last_name' => 'Kamau',
            'image_url' => 'https://img.clerk.com/avatar.png',
            'primary_email_address_id' => 'idn_1',
            'email_addresses' => [
                ['id' => 'idn_1', 'email_address' => 'wanjiru@yowimo.app'],
            ],
        ], $data),
    ];
}

it('rejects a webhook with no svix headers', function () {
    $payload = clerkUserPayload('user.created');

    $this->postJson('/api/v1/webhooks/clerk', $payload)
        ->assertStatus(400)
        ->assertJson(['success' => false]);
});

it('rejects a webhook with an invalid signature', function () {
    $webhook = $this->signedClerkWebhook(clerkUserPayload('user.created'));
    $headers = $webhook['headers'];
    $headers['svix-signature'] = 'v1,'.base64_encode('not-the-real-signature');

    $this->postClerkWebhook($webhook['body'], $headers)->assertStatus(400);
});

it('creates a local user for user.created', function () {
    $webhook = $this->signedClerkWebhook(clerkUserPayload('user.created'));

    $this->postClerkWebhook($webhook['body'], $webhook['headers'])->assertStatus(200);

    $user = User::where('clerk_user_id', 'user_webhook_1')->first();

    expect($user)->not->toBeNull();
    expect($user->email)->toBe('wanjiru@yowimo.app');
    expect($user->username)->toBe('websurfer');
    expect($user->first_name)->toBe('Wanjiru');
    expect($user->status)->toBe(UserStatus::Active);

    expect(WebhookEvent::where('event_id', $webhook['id'])->exists())->toBeTrue();
});

it('does not double-process a duplicate webhook delivery', function () {
    $webhook = $this->signedClerkWebhook(clerkUserPayload('user.created'));

    $this->postClerkWebhook($webhook['body'], $webhook['headers'])->assertStatus(200);
    $this->postClerkWebhook($webhook['body'], $webhook['headers'])->assertStatus(200);

    expect(User::where('clerk_user_id', 'user_webhook_1')->count())->toBe(1);
    expect(WebhookEvent::where('event_id', $webhook['id'])->count())->toBe(1);
});

it('synchronizes identity fields for user.updated', function () {
    User::factory()->create(['clerk_user_id' => 'user_webhook_1', 'username' => 'old-name']);

    $webhook = $this->signedClerkWebhook(clerkUserPayload('user.updated', ['username' => 'new-name']));

    $this->postClerkWebhook($webhook['body'], $webhook['headers'])->assertStatus(200);

    expect(User::where('clerk_user_id', 'user_webhook_1')->first()->username)->toBe('new-name');
});

it('deactivates and soft-deletes the local user for user.deleted', function () {
    User::factory()->create(['clerk_user_id' => 'user_webhook_1']);

    $webhook = $this->signedClerkWebhook([
        'type' => 'user.deleted',
        'data' => ['id' => 'user_webhook_1', 'deleted' => true, 'object' => 'user'],
    ]);

    $this->postClerkWebhook($webhook['body'], $webhook['headers'])->assertStatus(200);

    $user = User::withTrashed()->where('clerk_user_id', 'user_webhook_1')->first();

    expect($user->trashed())->toBeTrue();
    expect($user->status)->toBe(UserStatus::Deactivated);
});
