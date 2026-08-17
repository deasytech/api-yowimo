<?php

use App\Models\GameSession;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Tests\Support\FakesClerk;
use Tests\TestCase;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();

    // The "reverb" driver (Pusher-protocol compatible) is what actually runs the
    // registered Broadcast::channel() closures via verifyUserCanAccessChannel();
    // the "null" driver used elsewhere in tests (phpunit.xml) no-ops auth
    // entirely. Signing is local HMAC — no real Reverb server or network call
    // is needed. routes/channels.php only runs once, against whatever driver
    // was default at boot (null, per phpunit.xml) — switching the config here
    // doesn't move the already-registered channels, so they're re-registered
    // against the new default explicitly.
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-app-id',
    ]);
    require base_path('routes/channels.php');
});

function authenticateAsClerkUser(string $sub): User
{
    /** @var TestCase $test */
    $test = test();
    $token = $test->clerkToken(['sub' => $sub]);
    $test->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    return User::where('clerk_user_id', $sub)->firstOrFail();
}

function subscribeToChannel(string $sub, string $channelName)
{
    /** @var TestCase $test */
    $test = test();
    $token = $test->clerkToken(['sub' => $sub]);

    return $test->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/broadcasting/auth', [
            'channel_name' => $channelName,
            'socket_id' => '123.456',
        ]);
}

it('lets a party member subscribe to the party presence channel', function () {
    $member = authenticateAsClerkUser('user_channel_party_member');
    $party = Party::factory()->create();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $member->id]);

    subscribeToChannel('user_channel_party_member', "presence-party.{$party->id}")->assertOk();
});

it('rejects a non-member from the party presence channel', function () {
    authenticateAsClerkUser('user_channel_party_nonmember');
    $party = Party::factory()->create();

    subscribeToChannel('user_channel_party_nonmember', "presence-party.{$party->id}")->assertForbidden();
});

it('lets a party member subscribe to their game session private channel', function () {
    $member = authenticateAsClerkUser('user_channel_session_member');
    $party = Party::factory()->create();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $member->id]);
    $session = GameSession::factory()->create(['party_id' => $party->id]);

    subscribeToChannel('user_channel_session_member', "private-game-session.{$session->id}")->assertOk();
});

it('rejects a non-member from a game session private channel', function () {
    authenticateAsClerkUser('user_channel_session_nonmember');
    $party = Party::factory()->create();
    $session = GameSession::factory()->create(['party_id' => $party->id]);

    subscribeToChannel('user_channel_session_nonmember', "private-game-session.{$session->id}")->assertForbidden();
});

it('rejects subscribing to a game session channel that does not exist', function () {
    authenticateAsClerkUser('user_channel_session_missing');

    subscribeToChannel('user_channel_session_missing', 'private-game-session.999999')->assertForbidden();
});
