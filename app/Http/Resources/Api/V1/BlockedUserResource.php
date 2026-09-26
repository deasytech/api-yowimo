<?php

namespace App\Http\Resources\Api\V1;

use App\Models\BlockedUser;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin BlockedUser */
class BlockedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => PartyHostResource::make($this->whenLoaded('blocked')),
            'blocked_at' => $this->created_at,
        ];
    }
}
