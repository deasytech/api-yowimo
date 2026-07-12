<?php

namespace App\Services;

use App\Models\GameType;
use Illuminate\Pagination\CursorPaginator;

class GameTypeService
{
    /**
     * @param  array{search?: string|null, per_page?: int|null, cursor?: string|null}  $filters
     */
    public function list(array $filters): CursorPaginator
    {
        return GameType::query()
            ->where('is_active', true)
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('tagline', 'like', "%{$search}%");
                });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );
    }
}
