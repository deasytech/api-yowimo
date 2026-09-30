<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PartyMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One entry in a party's roster: the player's public identity plus their
 * membership. `user_id` matches the ids in a game's `turn_order` — guest
 * (pass-and-play) entries have no user_id and sit outside turn order.
 *
 * @mixin PartyMember
 */
class PartyPlayerResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'user' => $this->user_id ? new PartyHostResource($this->whenLoaded('user')) : null,
            'guest_name' => $this->guest_name,
            'guest_emoji' => $this->guest_emoji,
            'join_mode' => $this->join_mode?->value,
            'is_host' => $this->user_id === $this->party?->host_id,
            'status' => $this->status->value,
            'is_ready' => $this->is_ready,
            'joined_at' => $this->joined_at,
            'left_at' => $this->left_at,
        ];
    }
}
