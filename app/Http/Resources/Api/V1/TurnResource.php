<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Turn;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Turn */
class TurnResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'user_id' => $this->user_id,
            'card' => PackCardResource::make($this->whenLoaded('packCard')),
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
