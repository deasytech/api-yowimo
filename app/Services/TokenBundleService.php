<?php

namespace App\Services;

use App\Models\TokenBundle;
use Illuminate\Pagination\CursorPaginator;

class TokenBundleService
{
    /**
     * @param  array{per_page?: int|null, cursor?: string|null}  $filters
     */
    public function list(array $filters): CursorPaginator
    {
        return TokenBundle::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );
    }
}
