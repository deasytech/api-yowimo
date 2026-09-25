<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PartyMember;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PartyMember */
class PartyMembershipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'membership_status' => $this->status->value,
            'joined_at' => $this->joined_at,
            'left_at' => $this->left_at,
            'party' => new PartyResource($this->whenLoaded('party')),
        ];
    }
}
