<?php

namespace App\Services\Parties;

use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\Party;
use App\Models\User;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;

class PartyService
{
    public function __construct(private readonly RoomCodeGenerator $roomCodes) {}

    /**
     * List public, discoverable parties for the Discover feed.
     *
     * @param  array{mode?: string|null, game_type_id?: int|null, search?: string|null, per_page?: int|null, cursor?: string|null}  $filters
     */
    public function list(array $filters, ?User $viewer): CursorPaginator
    {
        return Party::query()
            ->with(['host', 'gameType', 'pack'])
            ->when($viewer, fn ($query) => $query->withExists([
                'likes as viewer_has_liked' => fn ($query) => $query->where('user_id', $viewer->id),
            ]))
            ->where('visibility', PartyVisibility::Public)
            ->whereIn('status', PartyStatus::publiclyVisible())
            ->when($filters['mode'] ?? null, fn ($query, $mode) => $query->where('mode', $mode))
            ->when($filters['game_type_id'] ?? null, fn ($query, $gameTypeId) => $query->where('game_type_id', $gameTypeId))
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', "%{$search}%")
                        ->orWhereHas('host', fn ($query) => $query->where('username', 'like', "%{$search}%"));
                });
            })
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );
    }

    public function find(int $id, ?User $viewer): Party
    {
        return Party::query()
            ->with(['host', 'gameType', 'pack'])
            ->when($viewer, fn ($query) => $query->withExists([
                'likes as viewer_has_liked' => fn ($query) => $query->where('user_id', $viewer->id),
            ]))
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $host, array $data): Party
    {
        return DB::transaction(function () use ($host, $data) {
            /** @var Party $party */
            $party = Party::create([
                'host_id' => $host->id,
                'game_type_id' => $data['game_type_id'] ?? null,
                'pack_id' => $data['pack_id'] ?? null,
                'room_code' => $this->roomCodes->generate(),
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'mode' => $data['mode'],
                'visibility' => $data['visibility'],
                'status' => $this->resolveStatus($data),
                'max_players' => $data['max_players'] ?? 8,
                'players_count' => 1,
                'starts_at' => $data['starts_at'] ?? null,
                'location' => $data['location'] ?? null,
                'tags' => $data['tags'] ?? [],
            ]);

            return $party->load(['host', 'gameType', 'pack']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveStatus(array $data): PartyStatus
    {
        if ($data['save_as_draft'] ?? false) {
            return PartyStatus::Draft;
        }

        if (! empty($data['starts_at'])) {
            return PartyStatus::Scheduled;
        }

        return PartyStatus::Live;
    }
}
