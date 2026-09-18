<?php

namespace App\Services;

use App\Models\Pack;
use App\Models\PackPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\CursorPaginator;

class PackService
{
    /**
     * @param  array{category?: string|null, game_type_id?: int|null, search?: string|null, per_page?: int|null, cursor?: string|null}  $filters
     */
    public function list(array $filters, ?User $viewer = null): CursorPaginator
    {
        $packs = $this->baseQuery($filters)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );

        $this->annotateOwnedByMe($packs, $viewer);

        return $packs;
    }

    /**
     * @param  array{per_page?: int|null, cursor?: string|null}  $filters
     */
    public function featured(array $filters, ?User $viewer = null): CursorPaginator
    {
        $packs = Pack::query()
            ->where('is_active', true)
            ->where('is_featured', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );

        $this->annotateOwnedByMe($packs, $viewer);

        return $packs;
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
     * Sets `owned_by_me` on every pack in this page via a single indexed
     * query (pack_purchases.user_id = ? AND pack_id IN (<ids on this
     * page>)), bounded by the page size rather than one query per pack.
     */
    private function annotateOwnedByMe(CursorPaginator $packs, ?User $viewer): void
    {
        if (! $viewer) {
            return;
        }

        $items = $packs->getCollection();

        $ownedPackIds = PackPurchase::query()
            ->where('user_id', $viewer->id)
            ->whereIn('pack_id', $items->pluck('id'))
            ->pluck('pack_id');

        $items->each(function (Pack $pack) use ($ownedPackIds) {
            $pack->owned_by_me = $ownedPackIds->contains($pack->id);
        });
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
