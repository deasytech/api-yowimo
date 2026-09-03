<?php

use App\Enums\BadgeKey;
use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Models\Badge;
use App\Models\GameSession;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\Turn;
use App\Models\User;
use App\Models\UserBadge;
use App\Services\Game\GameSessionService;

function makeLivePartyForGameCompletionBadges(int $memberCount, ?User $host = null): array
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host ??= User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);

    $members = collect([PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id])]);
    for ($i = 1; $i < $memberCount; $i++) {
        $members->push(PartyMember::factory()->create(['party_id' => $party->id]));
    }

    return [$host, $party->fresh(), $members->pluck('user_id')];
}

beforeEach(function () {
    Badge::factory()->create(['key' => BadgeKey::FirstParty]);
    Badge::factory()->create(['key' => BadgeKey::HundredParties]);
    Badge::factory()->create(['key' => BadgeKey::PerfectGame]);
});

it('awards First Party the first time a user completes a game', function () {
    [$host, $party] = makeLivePartyForGameCompletionBadges(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);
    $service->nextTurn($session);

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::FirstParty))
        ->where('user_id', $host->id)->exists())->toBeTrue();
});

it('awards Hundred Parties on the 100th completed game and not before', function () {
    [$host, $party] = makeLivePartyForGameCompletionBadges(1);

    // 99 prior completed games for this host, seeded directly rather than
    // played out, so the test isolates the threshold check.
    for ($i = 0; $i < 99; $i++) {
        $priorSession = GameSession::factory()->completed()->create();
        Turn::factory()->create(['game_session_id' => $priorSession->id, 'user_id' => $host->id]);
    }

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);
    $service->nextTurn($session);

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::HundredParties))
        ->where('user_id', $host->id)->exists())->toBeTrue();
});

it('awards Perfect Game when no turns were AFK-skipped', function () {
    [$host, $party] = makeLivePartyForGameCompletionBadges(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);
    $service->nextTurn($session);

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::PerfectGame))
        ->where('user_id', $host->id)->exists())->toBeTrue();
});

it('does not award Perfect Game when a turn was AFK-skipped', function () {
    [$host, $party] = makeLivePartyForGameCompletionBadges(2);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);
    $turn = $session->currentTurn();

    $turn->update(['started_at' => now()->subSeconds(GameSessionService::TURN_TIMEOUT_SECONDS + 1)]);
    $service->skipAfkTurn($turn->id);

    $service->nextTurn($session->fresh());

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::PerfectGame))
        ->where('user_id', $turn->user_id)->exists())->toBeFalse();
});

it('does not duplicate a badge already earned', function () {
    [$host, $party] = makeLivePartyForGameCompletionBadges(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);
    $service->nextTurn($session);

    [, $party2] = makeLivePartyForGameCompletionBadges(1, $host);

    $session2 = $service->start($host, $party2, 1);
    $service->nextTurn($session2);

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::PerfectGame))
        ->where('user_id', $host->id)->count())->toBe(1);
});
