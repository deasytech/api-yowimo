<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Round;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Round */
class RoundResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
        ];
    }
}
