<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\PartyVisibility;
use App\Models\Party;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Party */
class PartyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $isHost = $viewer && $viewer->id === $this->host_id;

        return [
            'id' => $this->id,
            'room_code' => $this->when(
                $isHost || $this->visibility === PartyVisibility::Public,
                $this->room_code
            ),
            'title' => $this->title,
            'description' => $this->description,
            'mode' => $this->mode->value,
            'visibility' => $this->visibility->value,
            'status' => $this->status->value,
            'max_players' => $this->max_players,
            'players_count' => $this->players_count,
            'likes_count' => $this->likes_count,
            'liked_by_me' => $this->isLikedBy($viewer),
            'is_sponsored' => $this->is_sponsored,
            'sponsor_name' => $this->sponsor_name,
            'tags' => $this->tags ?? [],
            'starts_at' => $this->starts_at,
            'location' => $this->location,
            'cover_image_url' => $this->cover_image_url,
            'gradient' => $this->gradient ?? [],
            'host' => PartyHostResource::make($this->whenLoaded('host')),
            'game_type' => GameTypeResource::make($this->whenLoaded('gameType')),
            'pack' => PackResource::make($this->whenLoaded('pack')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
