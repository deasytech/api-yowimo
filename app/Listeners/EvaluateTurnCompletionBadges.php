<?php

namespace App\Listeners;

use App\Enums\BadgeKey;
use App\Enums\PackCardKind;
use App\Events\TurnCompleted;
use App\Models\Turn;
use App\Models\User;
use App\Services\Game\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EvaluateTurnCompletionBadges implements ShouldQueue
{
    private const KIND_THRESHOLD = 25;

    public function __construct(private readonly BadgeService $badges) {}

    public function handle(TurnCompleted $event): void
    {
        if ($event->isAfk) {
            return;
        }

        $turn = Turn::find($event->turnId);

        if (! $turn || ! $turn->packCard) {
            return;
        }

        $user = User::find($event->userId);

        if (! $user) {
            return;
        }

        $completedCount = Turn::where('user_id', $event->userId)
            ->where('is_afk', false)
            ->whereHas('packCard', fn ($query) => $query->where('kind', $turn->packCard->kind))
            ->count();

        if ($completedCount < self::KIND_THRESHOLD) {
            return;
        }

        match ($turn->packCard->kind) {
            PackCardKind::Truth => $this->badges->award($user, BadgeKey::TruthMaster, $turn),
            PackCardKind::Dare => $this->badges->award($user, BadgeKey::DareDevil, $turn),
        };
    }
}
