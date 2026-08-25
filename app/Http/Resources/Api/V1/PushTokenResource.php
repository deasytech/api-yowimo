<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PushToken;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PushToken */
class PushTokenResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
