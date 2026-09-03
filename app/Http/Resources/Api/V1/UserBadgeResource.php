<?php

namespace App\Http\Resources\Api\V1;

use App\Models\UserBadge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin UserBadge */
class UserBadgeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'badge' => new BadgeResource($this->whenLoaded('badge')),
            'earned_at' => $this->earned_at,
        ];
    }
}
