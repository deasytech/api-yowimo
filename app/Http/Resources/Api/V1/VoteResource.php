<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Vote */
class VoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'turn_id' => $this->turn_id,
            'voter_id' => $this->voter_id,
            'category' => $this->category->value,
            'created_at' => $this->created_at,
        ];
    }
}
