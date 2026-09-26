<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PartyMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One entry in a party's roster: the player's public identity plus their
 * membership. `user_id` matches the ids in a game's `turn_order`.
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
            'user' => new PartyHostResource($this->whenLoaded('user')),
            'is_host' => $this->user_id === $this->party?->host_id,
            'status' => $this->status->value,
            'joined_at' => $this->joined_at,
            'left_at' => $this->left_at,
        ];
    }
}
