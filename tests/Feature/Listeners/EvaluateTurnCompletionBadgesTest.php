<?php

use App\Enums\BadgeKey;
use App\Enums\PackCardKind;
use App\Events\TurnCompleted;
use App\Listeners\EvaluateTurnCompletionBadges;
use App\Models\Badge;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Turn;
use App\Models\User;
use App\Models\UserBadge;

beforeEach(function () {
    Badge::factory()->create(['key' => BadgeKey::TruthMaster]);
    Badge::factory()->create(['key' => BadgeKey::DareDevil]);
});

function completeTurnForBadgeTest(User $user, PackCard $card, bool $isAfk = false): Turn
{
    $turn = Turn::factory()->create([
        'user_id' => $user->id,
        'pack_card_id' => $card->id,
        'is_afk' => $isAfk,
        'completed_at' => $isAfk ? null : now(),
    ]);

    app(EvaluateTurnCompletionBadges::class)->handle(
        new TurnCompleted($turn->game_session_id, $turn->round_id, $turn->id, $user->id, $isAfk)
    );

    return $turn;
}

it('awards Truth Master on the 25th completed Truth turn', function () {
    $host = User::factory()->create();
    $pack = Pack::factory()->create();
    $truthCard = PackCard::factory()->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);

    for ($i = 0; $i < 24; $i++) {
        completeTurnForBadgeTest($host, $truthCard);
    }

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::TruthMaster))
        ->where('user_id', $host->id)->exists())->toBeFalse();

    completeTurnForBadgeTest($host, $truthCard);

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::TruthMaster))
        ->where('user_id', $host->id)->exists())->toBeTrue();
});

it('awards Dare Devil on the 25th completed Dare turn, independently of Truth turns', function () {
    $host = User::factory()->create();
    $pack = Pack::factory()->create();
    $truthCard = PackCard::factory()->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    $dareCard = PackCard::factory()->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    for ($i = 0; $i < 10; $i++) {
        completeTurnForBadgeTest($host, $truthCard);
    }
    for ($i = 0; $i < 25; $i++) {
        completeTurnForBadgeTest($host, $dareCard);
    }

    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::DareDevil))
        ->where('user_id', $host->id)->exists())->toBeTrue();
    expect(UserBadge::whereHas('badge', fn ($q) => $q->where('key', BadgeKey::TruthMaster))
        ->where('user_id', $host->id)->exists())->toBeFalse();
});

it('does not count an AFK-skipped turn toward the threshold', function () {
    $host = User::factory()->create();
    $pack = Pack::factory()->create();
    $truthCard = PackCard::factory()->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);

    for ($i = 0; $i < 24; $i++) {
        completeTurnForBadgeTest($host, $truthCard);
    }

    completeTurnForBadgeTest($host, $truthCard, isAfk: true);

    expect(UserBadge::where('user_id', $host->id)->exists())->toBeFalse();
});
