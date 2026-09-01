<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Tests\Support\FakesClerk;
use Tests\TestCase;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
});

function voteEndpoint(int $gameSessionId, int $turnId): string
{
    return "/api/v1/game/{$gameSessionId}/turns/{$turnId}/vote";
}

/**
 * Provisions (via a real request, matching this app's auto-provision-on-first-
 * request behavior) and returns [User, bearer token] for a Clerk-authenticated
 * test user. Callers must generate the token with $this->clerkToken() and pass
 * it in, since clerkToken() is protected and unreachable from this free function.
 *
 * The `clerk` guard caches its resolved user for the lifetime of the app
 * instance, so it's forced to re-resolve before every call — otherwise a
 * second call with a different token would silently reuse the first user.
 *
 * @return array{0: User, 1: string}
 */
function provisionVoteTestUser(TestCase $test, string $token, string $sub): array
{
    app('auth')->forgetGuards();
    $test->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    return [User::where('clerk_user_id', $sub)->firstOrFail(), $token];
}

function makePartyForVoteTest(User $host, User $otherMember): Party
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count(10)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(10)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $party = Party::factory()->create([
        'host_id' => $host->id,
        'pack_id' => $pack->id,
        'status' => PartyStatus::Live,
    ]);

    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $otherMember->id]);

    return $party;
}

it('rejects casting a vote with no bearer token', function () {
    [$host, $hostToken] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_host_401']), 'user_vote_host_401');
    [$voter] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_voter_401']), 'user_vote_voter_401');
    $party = makePartyForVoteTest($host, $voter);

    $this->app->make('auth')->forgetGuards();
    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/parties/{$party->id}/game/start", ['rounds' => 5])
        ->assertStatus(200);

    $sessionId = $start->json('data.id');
    $turnId = $start->json('data.current_turn.id');

    // The `clerk` guard caches its resolved user for the app instance's
    // lifetime; force it to re-resolve so the missing header is actually honored.
    $this->app->make('auth')->forgetGuards();

    $this->withoutHeader('Authorization')
        ->postJson(voteEndpoint($sessionId, $turnId), ['category' => 'winner'])
        ->assertStatus(401);
});

it('lets a fellow party member cast a vote on a completed turn and credits XP to the turn player', function () {
    [$host, $hostToken] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_host_success']), 'user_vote_host_success');
    [$voter] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_voter_success']), 'user_vote_voter_success');
    $party = makePartyForVoteTest($host, $voter);

    $this->app->make('auth')->forgetGuards();
    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/parties/{$party->id}/game/start", ['rounds' => 5])
        ->assertStatus(200);

    $sessionId = $start->json('data.id');
    $turnId = $start->json('data.current_turn.id');
    $turnOwnerId = $start->json('data.current_turn.user_id');

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/game/{$sessionId}/next-turn")
        ->assertStatus(200);

    $voterToken = $turnOwnerId === $host->id
        ? $this->clerkToken(['sub' => 'user_vote_voter_success'])
        : $hostToken;
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$voterToken}")
        ->postJson(voteEndpoint($sessionId, $turnId), ['category' => 'winner'])
        ->assertStatus(200)
        ->assertJsonPath('data.turn_id', $turnId)
        ->assertJsonPath('data.category', 'winner');

    // 50 (Challenge Completed, granted automatically when the turn completed
    // via next-turn above) + 25 (the Winner vote just cast).
    $turnOwner = User::find($turnOwnerId)->fresh();
    expect($turnOwner->xp)->toBe(75);
});

it('forbids the turn player from voting on their own turn', function () {
    [$host, $hostToken] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_host_self']), 'user_vote_host_self');
    [$voter] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_voter_self']), 'user_vote_voter_self');
    $party = makePartyForVoteTest($host, $voter);

    $this->app->make('auth')->forgetGuards();
    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/parties/{$party->id}/game/start", ['rounds' => 5])
        ->assertStatus(200);

    $sessionId = $start->json('data.id');
    $turnId = $start->json('data.current_turn.id');
    $turnOwnerId = $start->json('data.current_turn.user_id');

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/game/{$sessionId}/next-turn")
        ->assertStatus(200);

    $ownerToken = $turnOwnerId === $host->id
        ? $hostToken
        : $this->clerkToken(['sub' => 'user_vote_voter_self']);
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$ownerToken}")
        ->postJson(voteEndpoint($sessionId, $turnId), ['category' => 'winner'])
        ->assertStatus(403);
});

it('forbids a non-party-member from voting', function () {
    [$host, $hostToken] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_host_outsider']), 'user_vote_host_outsider');
    [$voter] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_voter_outsider']), 'user_vote_voter_outsider');
    $party = makePartyForVoteTest($host, $voter);

    $this->app->make('auth')->forgetGuards();
    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/parties/{$party->id}/game/start", ['rounds' => 5])
        ->assertStatus(200);

    $sessionId = $start->json('data.id');
    $turnId = $start->json('data.current_turn.id');

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/game/{$sessionId}/next-turn")
        ->assertStatus(200);

    $outsiderToken = $this->clerkToken(['sub' => 'user_vote_outsider']);
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$outsiderToken}")
        ->postJson(voteEndpoint($sessionId, $turnId), ['category' => 'winner'])
        ->assertStatus(403);
});

it('rejects voting on a turn that has not completed yet', function () {
    [$host, $hostToken] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_host_incomplete']), 'user_vote_host_incomplete');
    [$voter] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_voter_incomplete']), 'user_vote_voter_incomplete');
    $party = makePartyForVoteTest($host, $voter);

    $this->app->make('auth')->forgetGuards();
    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/parties/{$party->id}/game/start", ['rounds' => 5])
        ->assertStatus(200);

    $sessionId = $start->json('data.id');
    $turnId = $start->json('data.current_turn.id');
    $turnOwnerId = $start->json('data.current_turn.user_id');

    $voterToken = $turnOwnerId === $host->id
        ? $this->clerkToken(['sub' => 'user_vote_voter_incomplete'])
        : $hostToken;
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$voterToken}")
        ->postJson(voteEndpoint($sessionId, $turnId), ['category' => 'winner'])
        ->assertStatus(422);
});

it('rejects casting the same category of vote twice on the same turn', function () {
    [$host, $hostToken] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_host_dup']), 'user_vote_host_dup');
    [$voter] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_voter_dup']), 'user_vote_voter_dup');
    $party = makePartyForVoteTest($host, $voter);

    $this->app->make('auth')->forgetGuards();
    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/parties/{$party->id}/game/start", ['rounds' => 5])
        ->assertStatus(200);

    $sessionId = $start->json('data.id');
    $turnId = $start->json('data.current_turn.id');
    $turnOwnerId = $start->json('data.current_turn.user_id');

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/game/{$sessionId}/next-turn")
        ->assertStatus(200);

    $voterToken = $turnOwnerId === $host->id
        ? $this->clerkToken(['sub' => 'user_vote_voter_dup'])
        : $hostToken;
    $this->app->make('auth')->forgetGuards();

    $this->withHeader('Authorization', "Bearer {$voterToken}")
        ->postJson(voteEndpoint($sessionId, $turnId), ['category' => 'winner'])
        ->assertStatus(200);

    $this->withHeader('Authorization', "Bearer {$voterToken}")
        ->postJson(voteEndpoint($sessionId, $turnId), ['category' => 'winner'])
        ->assertStatus(409);
});

it('rejects an invalid vote category', function () {
    [$host, $hostToken] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_host_invalid']), 'user_vote_host_invalid');
    [$voter] = provisionVoteTestUser($this, $this->clerkToken(['sub' => 'user_vote_voter_invalid']), 'user_vote_voter_invalid');
    $party = makePartyForVoteTest($host, $voter);

    $this->app->make('auth')->forgetGuards();
    $start = $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/parties/{$party->id}/game/start", ['rounds' => 5])
        ->assertStatus(200);

    $sessionId = $start->json('data.id');
    $turnId = $start->json('data.current_turn.id');

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson("/api/v1/game/{$sessionId}/next-turn")
        ->assertStatus(200);

    $this->withHeader('Authorization', "Bearer {$hostToken}")
        ->postJson(voteEndpoint($sessionId, $turnId), ['category' => 'not-a-category'])
        ->assertStatus(422);
});
