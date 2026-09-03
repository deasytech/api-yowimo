<?php

namespace App\Services\Game;

use App\Enums\BadgeKey;
use App\Events\BadgeAwarded;
use App\Models\Badge;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BadgeService
{
    /**
     * @param  array{per_page?: int|null, cursor?: string|null}  $filters
     */
    public function list(array $filters): CursorPaginator
    {
        return Badge::query()
            ->orderBy('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );
    }

    /**
     * Award a badge to a user, if they don't already have it.
     *
     * Returns null when the badge was already earned (no duplicate row, no
     * event) rather than throwing, since callers check this on every
     * qualifying event and "already earned" is the expected steady state.
     * Also returns null (rather than throwing) if the badge catalog row
     * doesn't exist yet — badge evaluation is a side effect of gameplay
     * events and must never fail the gameplay action it's attached to.
     */
    public function award(User $user, BadgeKey $key, ?Model $reference = null): ?UserBadge
    {
        $badge = Badge::query()->where('key', $key)->first();

        if (! $badge) {
            return null;
        }

        return DB::transaction(function () use ($user, $badge, $reference) {
            try {
                $userBadge = UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'reference_type' => $reference?->getMorphClass(),
                    'reference_id' => $reference?->getKey(),
                ]);
            } catch (QueryException $exception) {
                if (! $this->isDuplicateBadgeViolation($exception)) {
                    throw $exception;
                }

                return null;
            }

            BadgeAwarded::dispatch($user->id, $badge->id);

            return $userBadge;
        });
    }

    private function isDuplicateBadgeViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $matchesColumns = Str::contains($message, 'user_id') && Str::contains($message, 'badge_id');
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $matchesColumns && $isUniqueViolation;
    }
}
