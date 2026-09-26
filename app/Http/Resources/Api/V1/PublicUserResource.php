<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\FriendshipStatus;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Another user's profile as seen by the viewer. Deliberately a small,
 * public-safe subset — never email, wallet, date of birth, or other private
 * fields exposed by UserResource (which is only ever for the user themself).
 *
 * @mixin User
 */
class PublicUserResource extends JsonResource
{
    public function __construct(User $resource, private readonly ?Friendship $friendship = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'xp' => $this->xp,
            'badges' => UserBadgeResource::collection($this->whenLoaded('badges')),
            'friendship' => [
                'id' => $this->friendship?->id,
                'status' => $this->friendshipStatus($request->user()),
            ],
        ];
    }

    /**
     * One of: self, none, friends, request_sent, request_received.
     */
    private function friendshipStatus(User $viewer): string
    {
        return match (true) {
            $viewer->id === $this->id => 'self',
            ! $this->friendship => 'none',
            $this->friendship->status === FriendshipStatus::Accepted => 'friends',
            $this->friendship->sender_id === $viewer->id => 'request_sent',
            default => 'request_received',
        };
    }
}
