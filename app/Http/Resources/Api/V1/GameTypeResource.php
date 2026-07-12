<?php

namespace App\Http\Resources\Api\V1;

use App\Models\GameType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin GameType */
class GameTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'emoji' => $this->emoji,
            'tagline' => $this->tagline,
            'audience' => $this->audience,
            'intensity' => $this->intensity->value,
            'cost' => $this->cost,
            'image_url' => $this->image_url,
            'gradient' => $this->gradient ?? [],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
