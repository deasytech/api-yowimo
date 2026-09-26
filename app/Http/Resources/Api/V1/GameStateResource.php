<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\PartyMemberStatus;
use App\Models\GameSession;
use App\Models\PartyMember;
use Illuminate\Http\Request;

/**
 * Full game session state for a client (re)loading a game: everything in
 * GameSessionResource plus the fields needed to render the table without
 * having received earlier realtime events. A separate resource so the
 * start/next-turn response shape stays unchanged.
 *
 * @mixin GameSession
 */
class GameStateResource extends GameSessionResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'host_id' => $this->host_id,
            'pack_id' => $this->pack_id,
            'turn_order' => $this->turn_order ?? [],
            'active_player_ids' => $this->activePlayerIds(),
            'current_turn_index' => $this->current_turn_index,
            'turn_seconds' => $this->turn_seconds,
            'paused_at' => $this->paused_at,
            'paused_turn_remaining_seconds' => $this->paused_turn_remaining_seconds,
            'voting_ends_at' => $this->voting_ends_at,
        ];
    }

    /**
     * The players in `turn_order` still in the party, in turn order. Players
     * who left stay in `turn_order` (it's positional), but are skipped.
     *
     * @return array<int, int>
     */
    private function activePlayerIds(): array
    {
        $active = PartyMember::query()
            ->where('party_id', $this->party_id)
            ->where('status', PartyMemberStatus::Active)
            ->pluck('user_id')
            ->all();

        return array_values(array_filter($this->turn_order ?? [], fn ($userId) => in_array($userId, $active, true)));
    }
}
