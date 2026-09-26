<?php

namespace App\Http\Resources\Api\V1;

use App\Models\GameSession;
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
            'current_turn_index' => $this->current_turn_index,
        ];
    }
}
