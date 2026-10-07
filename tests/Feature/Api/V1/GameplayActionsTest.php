<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Models\GameSession;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Services\Game\GameSessionService;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakesClerk;

uses(FakesClerk::class);

beforeEach(function () {
    $this->fakeClerk();
    Queue::fake();
});

/**
 * A live party whose host and one other member are Clerk-authenticatable,
 * with a running game started by the host.
 *
 * @return array{party: Party, session: GameSession, host: User, member: User}
 */
function startGameForActions(string $prefix): array
{
    $pack = Pack::factory()->create(['price' => 0]);
    PackCard::factory()->count(10)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(10)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create(['clerk_user_id' => "{$prefix}_host"]);
    $member = User::factory()->create(['clerk_user_id' => "{$prefix}_member"]);
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $member->id]);

    $session = app(GameSessionService::class)->start($host, $party, 5);

    return ['party' => $party, 'session' => $session, 'host' => $host, 'member' => $member];
}

function actAsClerkUser(User $user): void
{
    app('auth')->forgetGuards();
    test()->withHeader('Authorization', 'Bearer '.test()->clerkToken(['sub' => $user->clerk_user_id]));
}

function turnActionEndpoint(GameSession $session, string $action): string
{
    $turnId = $session->fresh()->currentTurn()->id;

    return "/api/v1/game/{$session->id}/turns/{$turnId}/{$action}";
}

it('lets the current player complete their own turn', function () {
    $game = startGameForActions('actions_complete');
    $player = User::find($game['session']->currentTurn()->user_id);
    actAsClerkUser($player);

    $this->postJson(turnActionEndpoint($game['session'], 'complete'))
        ->assertOk()
        ->assertJsonPath('message', 'Turn completed.')
        ->assertJsonPath('data.current_turn.position', 1);
});

it('lets the current player skip their own turn', function () {
    $game = startGameForActions('actions_skip');
    $turn = $game['session']->currentTurn();
    actAsClerkUser(User::find($turn->user_id));

    $this->postJson(turnActionEndpoint($game['session'], 'skip'))
        ->assertOk()
        ->assertJsonPath('message', 'Turn skipped.');

    expect($turn->fresh()->is_skipped)->toBeTrue();
});

it('lets the host act on another player\'s turn but not a non-host on someone else\'s', function () {
    $game = startGameForActions('actions_authz');
    $service = app(GameSessionService::class);
    $session = $game['session'];

    // The member holds the turn: the host may act on it.
    while ($session->fresh()->currentTurn()->user_id !== $game['member']->id) {
        $session = $service->nextTurn($session);
    }
    actAsClerkUser($game['host']);
    $this->postJson(turnActionEndpoint($session, 'complete'))->assertOk();

    // The host holds the turn: the member may not act on it.
    while ($session->fresh()->currentTurn()->user_id !== $game['host']->id) {
        $session = $service->nextTurn($session);
    }
    actAsClerkUser($game['member']);
    $this->postJson(turnActionEndpoint($session, 'complete'))->assertStatus(403);
});

it('returns 409 for a stale turn and 404 for a turn from another game', function () {
    $game = startGameForActions('actions_stale');
    $staleEndpoint = turnActionEndpoint($game['session'], 'complete');
    actAsClerkUser($game['host']);

    $this->postJson($staleEndpoint)->assertOk();
    $this->postJson($staleEndpoint)->assertStatus(409)->assertJsonPath('message', 'This turn is no longer active.');

    $otherGame = startGameForActions('actions_stale_other');
    $otherTurnId = $otherGame['session']->currentTurn()->id;
    $this->postJson("/api/v1/game/{$game['session']->id}/turns/{$otherTurnId}/complete")->assertStatus(404);
});

it('lets only the host pause and resume', function () {
    $game = startGameForActions('actions_pause');

    actAsClerkUser($game['member']);
    $this->postJson("/api/v1/game/{$game['session']->id}/pause")->assertStatus(403);

    actAsClerkUser($game['host']);
    $this->postJson("/api/v1/game/{$game['session']->id}/pause")
        ->assertOk()
        ->assertJsonPath('data.status', 'paused')
        ->assertJsonStructure(['data' => ['paused_at', 'paused_turn_remaining_seconds', 'turn_seconds']]);
    $this->postJson("/api/v1/game/{$game['session']->id}/pause")->assertStatus(422);

    $this->postJson("/api/v1/game/{$game['session']->id}/resume")
        ->assertOk()
        ->assertJsonPath('data.status', 'running')
        ->assertJsonPath('data.paused_at', null);
});

it('finds a party\'s game for members only', function () {
    $game = startGameForActions('actions_current');

    actAsClerkUser($game['member']);
    $this->getJson("/api/v1/parties/{$game['party']->id}/game")
        ->assertOk()
        ->assertJsonPath('data.id', $game['session']->id)
        ->assertJsonPath('data.active_player_ids', $game['session']->turn_order);

    actAsClerkUser(User::factory()->create(['clerk_user_id' => 'actions_current_outsider']));
    $this->getJson("/api/v1/parties/{$game['party']->id}/game")->assertStatus(403);
});

it('returns 404 when a party has no game yet', function () {
    $host = User::factory()->create(['clerk_user_id' => 'actions_no_game_host']);
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    actAsClerkUser($host);

    $this->getJson("/api/v1/parties/{$party->id}/game")->assertStatus(404);
});

it('returns the game results to members', function () {
    $game = startGameForActions('actions_results');
    actAsClerkUser($game['member']);

    $this->getJson("/api/v1/game/{$game['session']->id}/results")
        ->assertOk()
        ->assertJsonPath('data.game_session_id', $game['session']->id)
        ->assertJsonPath('data.status', 'running')
        ->assertJsonCount(2, 'data.standings')
        ->assertJsonStructure(['data' => ['standings' => [['user' => ['id', 'username'], 'xp', 'votes' => ['winner', 'funny', 'creativity'], 'turns' => ['completed', 'skipped', 'afk'], 'is_mvp']]]]);
});

it('accepts a supported reaction from a member and rejects others', function () {
    $game = startGameForActions('actions_reactions');
    $endpoint = "/api/v1/game/{$game['session']->id}/reactions";

    actAsClerkUser($game['member']);
    $this->postJson($endpoint, ['emoji' => '😂'])->assertOk()->assertJsonPath('message', 'Reaction sent.');
    $this->postJson($endpoint, ['emoji' => '🍕'])->assertStatus(422);

    actAsClerkUser(User::factory()->create(['clerk_user_id' => 'actions_reactions_outsider']));
    $this->postJson($endpoint, ['emoji' => '😂'])->assertStatus(403);
});

it('validates the host-chosen turn timer when starting a game', function () {
    $game = startGameForActions('actions_timer');
    app(GameSessionService::class)->endForParty($game['party']);
    actAsClerkUser($game['host']);

    $this->postJson("/api/v1/parties/{$game['party']->id}/game/start", ['turn_seconds' => 20])->assertStatus(422);
    $this->postJson("/api/v1/parties/{$game['party']->id}/game/start", ['turn_seconds' => 45])
        ->assertOk();

    expect(GameSession::where('party_id', $game['party']->id)->latest('id')->first()->turn_seconds)->toBe(45);
});

it('returns the running session id when the host tries to start a second game', function () {
    $game = startGameForActions('actions_second_start');
    actAsClerkUser($game['host']);

    $this->postJson("/api/v1/parties/{$game['party']->id}/game/start")
        ->assertStatus(409)
        ->assertJsonPath('errors.game_session_id', $game['session']->id);
});
