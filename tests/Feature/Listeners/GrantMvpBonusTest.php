<?php

use App\Enums\BadgeKey;
use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Enums\VoteCategory;
use App\Enums\XpTransactionType;
use App\Listeners\GrantMvpBonus;
use App\Models\Badge;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\Turn;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\XpTransaction;
use App\Services\Game\GameSessionService;
use App\Services\Game\VoteService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Queue;

function makeLivePartyForMvpBonus(int $memberCount): array
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(20)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);

    $members = collect([PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id])]);
    for ($i = 1; $i < $memberCount; $i++) {
        $members->push(PartyMember::factory()->create(['party_id' => $party->id]));
    }

    return [$host, $party->fresh(), $members->pluck('user_id')];
}

it('pushes the MVP-bonus listener onto the queue when GameCompleted fires, but only after the final turn\'s Challenge Completed XP already landed', function () {
    [$host, $party] = makeLivePartyForMvpBonus(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);
    $lastTurnId = $session->currentTurn()->id;

    Queue::fake();

    // Single member, single round: this one nextTurn() call completes the
    // only (and therefore last) turn, which also completes the game.
    $service->nextTurn($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === GrantMvpBonus::class);

    // GrantMvpBonus's job is only *queued* here, not yet run (Queue::fake()
    // never executes it) — proving this row already exists is what proves
    // GrantChallengeCompletionXp (deliberately not ShouldQueue) already ran
    // and committed before GameCompleted, and therefore GrantMvpBonus's job,
    // was even dispatched. If GrantChallengeCompletionXp ever became
    // ShouldQueue again, this assertion would fail: Queue::fake() would
    // intercept it too, and this row wouldn't exist yet.
    expect(XpTransaction::where('reference_type', Turn::class)
        ->where('reference_id', $lastTurnId)
        ->where('type', XpTransactionType::ChallengeCompleted)
        ->exists())->toBeTrue();
});

it('awards the MVP bonus to every player tied for the highest XP when nobody voted', function () {
    [$host, $party, $userIds] = makeLivePartyForMvpBonus(2);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    foreach ($session->turn_order as $ignored) {
        $session = $service->nextTurn($session->fresh());
    }

    foreach ($userIds as $userId) {
        $user = User::find($userId);
        expect($user->xp)->toBe(150); // 50 (challenge completed) + 100 (MVP, tied)
    }
});

it('awards the MVP bonus only to the sole top scorer when votes differentiate players', function () {
    [$host, $party, $userIds] = makeLivePartyForMvpBonus(3);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    $firstTurn = $session->currentTurn();
    $firstTurnOwnerId = $firstTurn->user_id;

    $session = $service->nextTurn($session);

    $voterId = $userIds->first(fn ($id) => $id !== $firstTurnOwnerId);
    app(VoteService::class)->cast(User::find($voterId), Turn::find($firstTurn->id), VoteCategory::Winner);

    $session = $service->nextTurn($session->fresh());
    $service->nextTurn($session->fresh());

    $topScorer = User::find($firstTurnOwnerId);
    expect($topScorer->xp)->toBe(175); // 50 (own challenge) + 25 (winner vote) + 100 (MVP)

    foreach ($userIds as $userId) {
        if ($userId === $firstTurnOwnerId) {
            continue;
        }

        expect(User::find($userId)->xp)->toBe(50);
    }

    $mvpTransaction = XpTransaction::where('user_id', $firstTurnOwnerId)
        ->where('type', XpTransactionType::MvpBonus)
        ->first();

    expect($mvpTransaction)->not->toBeNull();
    expect($mvpTransaction->amount)->toBe(100);
});

it('awards the Party King badge to the MVP bonus recipient', function () {
    Badge::factory()->create(['key' => BadgeKey::PartyKing]);

    [$host, $party] = makeLivePartyForMvpBonus(1);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);
    $service->nextTurn($session);

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::PartyKing))
        ->where('user_id', $host->id)->exists())->toBeTrue();
});
