<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Friendship;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Friendship */
class FriendResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $friend = $this->sender_id === $viewer->id ? $this->receiver : $this->sender;

        return [
            'friendship_id' => $this->id,
            'friend' => new PartyHostResource($friend),
            'accepted_at' => $this->accepted_at,
        ];
    }
}
