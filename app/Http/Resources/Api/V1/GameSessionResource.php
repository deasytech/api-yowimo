<?php

namespace App\Http\Resources\Api\V1;

use App\Models\GameSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GameSession */
class GameSessionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'party_id' => $this->party_id,
            'status' => $this->status->value,
            'rounds_count' => $this->rounds_count,
            'current_round_number' => $this->current_round_number,
            'started_at' => $this->started_at,
            'ended_at' => $this->ended_at,
            'current_round' => RoundResource::make($this->resource->currentRound()),
            'current_turn' => TurnResource::make($this->resource->currentTurn()?->load('packCard')),
        ];
    }
}
