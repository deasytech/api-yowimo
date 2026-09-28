<?php

namespace App\Services\Game;

use App\Models\User;
use Illuminate\Pagination\CursorPaginator;

class LeaderboardService
{
    /**
     * All-time global standings by total XP earned. Soft-deleted (including
     * deactivated) accounts are excluded by User's default SoftDeletes scope.
     *
     * @param  array{per_page?: int|null, cursor?: string|null}  $filters
     */
    public function global(array $filters): CursorPaginator
    {
        return User::query()
            ->orderByDesc('xp')
            ->orderBy('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );
    }
}
