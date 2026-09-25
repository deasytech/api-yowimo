<?php

namespace App\Services\Parties;

use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Events\PartyCreated;
use App\Exceptions\Api\PartyGameAlreadyStartedException;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PartyService
{
    public function __construct(
        private readonly RoomCodeGenerator $roomCodes,
        private readonly PartyCoverImageService $coverImages,
    ) {}

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
                'members as viewer_is_member' => fn ($query) => $query->where('user_id', $viewer->id),
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
                'members as viewer_is_member' => fn ($query) => $query->where('user_id', $viewer->id),
            ]))
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $host, array $data, ?UploadedFile $coverImage = null): Party
    {
        // Uploaded once here, not inside attemptInsert(): that can run twice
        // on a room-code retry, which would otherwise re-optimize and store
        // the same image twice and orphan the first copy.
        $coverImageUrl = $coverImage ? $this->coverImages->store($coverImage) : null;

        return DB::transaction(function () use ($host, $data, $coverImageUrl) {
            try {
                $party = $this->attemptInsert($host, $data, $this->roomCodes->generate(), $coverImageUrl);
            } catch (QueryException $exception) {
                if (! $this->isRoomCodeUniqueViolation($exception)) {
                    throw $exception;
                }

                // Lost a race to another concurrent create() for the same
                // room code; regenerate and retry once. Each attempt runs in
                // its own nested transaction/savepoint so the failed first
                // insert is rolled back cleanly instead of aborting the
                // outer transaction before the retry runs.
                $party = $this->attemptInsert($host, $data, $this->roomCodes->generate(), $coverImageUrl);
            }

            return $party->load(['host', 'gameType', 'pack']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function attemptInsert(User $host, array $data, string $roomCode, ?string $coverImageUrl): Party
    {
        return DB::transaction(fn () => $this->insertParty($host, $data, $roomCode, $coverImageUrl));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function insertParty(User $host, array $data, string $roomCode, ?string $coverImageUrl): Party
    {
        $party = Party::create([
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
            'cover_image_url' => $coverImageUrl,
        ]);

        PartyMember::create([
            'party_id' => $party->id,
            'user_id' => $host->id,
            'joined_at' => now(),
        ]);

        PartyCreated::dispatch($party->id, $host->id);

        return $party;
    }

    /**
     * Sets the party's game type/pack selection — the only way to fill this
     * in when it wasn't chosen at creation, since both are optional there.
     * Only settable while no game session has started yet: once turns/rounds
     * have been dealt from the original pack, changing it retroactively
     * would be inconsistent with data already created.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws PartyGameAlreadyStartedException if a game session already exists for this party.
     */
    public function update(Party $party, array $data): Party
    {
        $changes = Arr::only($data, ['game_type_id', 'pack_id']);

        if ($changes !== [] && $party->gameSessions()->exists()) {
            throw new PartyGameAlreadyStartedException;
        }

        $party->fill($changes);
        $party->save();

        return $party->load(['host', 'gameType', 'pack']);
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
