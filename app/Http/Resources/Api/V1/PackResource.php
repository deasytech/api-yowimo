<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Pack;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Pack */
class PackResource extends JsonResource
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
            'tag' => $this->tag,
            'category' => $this->category->value,
            'description' => $this->description,
            'price' => $this->price,
            'truths_count' => $this->truths_count,
            'dares_count' => $this->dares_count,
            'cards_count' => $this->cards_count,
            'cover_image_url' => $this->cover_image_url,
            'gradient' => $this->gradient ?? [],
            'is_featured' => $this->is_featured,
            'game_type' => GameTypeResource::make($this->whenLoaded('gameType')),
            'preview_cards' => PackCardResource::collection($this->whenLoaded('cards')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
