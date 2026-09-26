<?php

use App\Enums\FriendshipStatus;
use App\Enums\PartyMemberStatus;
use App\Enums\PartyStatus;
use App\Enums\UserStatus;
use App\Models\Friendship;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\PushToken;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Tests\Support\FakesClerk;

const CLERK_USERS_API = 'https://api.clerk.com/v1/users/*';

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
    config(['services.clerk.secret_key' => 'sk_test_deletion']);
});

it('rejects account deletion with no bearer token', function () {
    $this->deleteJson(API_V1_ME_ENDPOINT)->assertStatus(401);
});

it('deletes the Clerk user and soft-deletes the local account', function () {
    Http::fake([CLERK_USERS_API => Http::response(['deleted' => true], 200)]);
    $user = authAs('user_delete_me');

    $this->deleteJson(API_V1_ME_ENDPOINT)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Account deleted successfully.');

    Http::assertSent(fn (HttpRequest $request) => $request->method() === 'DELETE'
        && $request->url() === 'https://api.clerk.com/v1/users/user_delete_me'
        && $request->hasHeader('Authorization', 'Bearer sk_test_deletion'));

    $user = User::withTrashed()->findOrFail($user->id);
    expect($user->trashed())->toBeTrue();
    expect($user->status)->toBe(UserStatus::Deactivated);
});

it('treats a user already missing from Clerk as deleted', function () {
    Http::fake([CLERK_USERS_API => Http::response(['errors' => []], 404)]);
    $user = authAs('user_delete_missing_in_clerk');

    $this->deleteJson(API_V1_ME_ENDPOINT)->assertOk();

    expect(User::withTrashed()->findOrFail($user->id)->trashed())->toBeTrue();
});

it('changes nothing locally when Clerk rejects the deletion', function () {
    Http::fake([CLERK_USERS_API => Http::response(['errors' => []], 500)]);
    $user = authAs('user_delete_clerk_fails');

    $this->deleteJson(API_V1_ME_ENDPOINT)
        ->assertStatus(502)
        ->assertJsonPath('success', false);

    $user->refresh();
    expect($user->trashed())->toBeFalse();
    expect($user->status)->toBe(UserStatus::Active);
});

it('changes nothing locally when the Clerk secret key is not configured', function () {
    config(['services.clerk.secret_key' => null]);
    $user = authAs('user_delete_no_key');

    $this->deleteJson(API_V1_ME_ENDPOINT)->assertStatus(502);

    expect($user->refresh()->trashed())->toBeFalse();
});

it('rejects the deleted users token on later requests', function () {
    Http::fake([CLERK_USERS_API => Http::response([], 200)]);
    authAs('user_delete_then_reuse');

    $this->deleteJson(API_V1_ME_ENDPOINT)->assertOk();

    $this->app->make('auth')->forgetGuards();

    $this->getJson(API_V1_ME_ENDPOINT)->assertStatus(401);
});

it('cancels unstarted hosted parties, ends live ones, and leaves joined ones', function () {
    Http::fake([CLERK_USERS_API => Http::response([], 200)]);
    $user = authAs('user_delete_parties');

    $draft = Party::factory()->create(['host_id' => $user->id, 'status' => PartyStatus::Draft]);
    $live = Party::factory()->create(['host_id' => $user->id, 'status' => PartyStatus::Live]);
    $ended = Party::factory()->create(['host_id' => $user->id, 'status' => PartyStatus::Ended]);

    $joined = Party::factory()->create(['status' => PartyStatus::Live, 'players_count' => 2]);
    $membership = PartyMember::factory()->create(['party_id' => $joined->id, 'user_id' => $user->id]);

    $this->deleteJson(API_V1_ME_ENDPOINT)->assertOk();

    expect($draft->refresh()->status)->toBe(PartyStatus::Cancelled);
    expect($live->refresh()->status)->toBe(PartyStatus::Ended);
    expect($ended->refresh()->status)->toBe(PartyStatus::Ended);
    expect($joined->refresh()->status)->toBe(PartyStatus::Live);
    expect($joined->players_count)->toBe(1);
    expect($membership->refresh()->status)->toBe(PartyMemberStatus::Left);
});

it('closes friendships, removes the push token, and keeps the wallet', function () {
    Http::fake([CLERK_USERS_API => Http::response([], 200)]);
    $user = authAs('user_delete_social');

    $friend = Friendship::factory()->accepted()->create(['sender_id' => $user->id]);
    $outgoing = Friendship::factory()->create(['sender_id' => $user->id]);
    $incoming = Friendship::factory()->create(['receiver_id' => $user->id]);
    PushToken::factory()->create(['user_id' => $user->id]);
    $wallet = Wallet::factory()->create(['user_id' => $user->id]);

    $this->deleteJson(API_V1_ME_ENDPOINT)->assertOk();

    expect($friend->refresh()->status)->toBe(FriendshipStatus::Removed);
    expect($outgoing->refresh()->status)->toBe(FriendshipStatus::Cancelled);
    expect($incoming->refresh()->status)->toBe(FriendshipStatus::Rejected);
    expect(PushToken::where('user_id', $user->id)->exists())->toBeFalse();
    expect(Wallet::whereKey($wallet->id)->exists())->toBeTrue();
});
