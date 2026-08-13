<?php

namespace App\Services;

use App\Models\Pack;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;

class PackService
{
    /**
     * @param  array{category?: string|null, game_type_id?: int|null, search?: string|null, per_page?: int|null, cursor?: string|null}  $filters
     */
    public function list(array $filters): CursorPaginator
    {
        return $this->baseQuery($filters)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );
    }

    /**
     * @param  array{per_page?: int|null, cursor?: string|null}  $filters
     */
    public function featured(array $filters): CursorPaginator
    {
        return Pack::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );
    }

    /**
     * Find an active pack. When a viewer is given, the response also carries
     * an `owned_by_me` flag, and the full (non-preview) card set is loaded
     * for a viewer who owns the pack instead of the preview-only subset.
     */
    public function find(int $id, ?User $viewer = null): Pack
    {
        $pack = Pack::query()
            ->with('gameType')
            ->when($viewer, fn ($query) => $query->withExists([
                'purchases as owned_by_me' => fn ($query) => $query->where('user_id', $viewer->id),
            ]))
            ->where('is_active', true)
            ->findOrFail($id);

        $pack->load(['cards' => fn ($query) => $pack->owned_by_me
            ? $query->orderBy('position')
            : $query->where('is_preview', true)->orderBy('position')]);

        return $pack;
    }

    /**
     * @param  array{category?: string|null, game_type_id?: int|null, search?: string|null}  $filters
     * @return Builder<Pack>
     */
    private function baseQuery(array $filters): Builder
    {
        return Pack::query()
            ->where('is_active', true)
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category', $category))
            ->when($filters['game_type_id'] ?? null, fn ($query, $gameTypeId) => $query->where('game_type_id', $gameTypeId))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            });
    }
}
