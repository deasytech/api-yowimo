<?php

namespace App\Services\Parties;

use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\Party;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            try {
                $party = $this->insertParty($host, $data, $this->roomCodes->generate());
            } catch (QueryException $exception) {
                if (! $this->isRoomCodeUniqueViolation($exception)) {
                    throw $exception;
                }

                // Lost a race to another concurrent create() for the same
                // room code; regenerate and retry once.
                $party = $this->insertParty($host, $data, $this->roomCodes->generate());
            }

            return $party->load(['host', 'gameType', 'pack']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function insertParty(User $host, array $data, string $roomCode): Party
    {
        return Party::create([
            'host_id' => $host->id,
            'game_type_id' => $data['game_type_id'] ?? null,
            'pack_id' => $data['pack_id'] ?? null,
            'room_code' => $roomCode,
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
    }

    private function isRoomCodeUniqueViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $isRoomCodeConstraint = Str::contains($message, ['room_code', 'parties_room_code_unique']);
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $isRoomCodeConstraint && $isUniqueViolation;
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
