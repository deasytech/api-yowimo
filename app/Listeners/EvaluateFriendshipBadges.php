<?php

namespace App\Listeners;

use App\Enums\BadgeKey;
use App\Enums\FriendshipStatus;
use App\Events\FriendRequestAccepted;
use App\Models\Friendship;
use App\Models\User;
use App\Services\Game\BadgeService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EvaluateFriendshipBadges implements ShouldQueue
{
    private const THRESHOLD = 10;

    public function __construct(private readonly BadgeService $badges) {}

    public function handle(FriendRequestAccepted $event): void
    {
        $friendship = Friendship::find($event->friendshipId);

        if (! $friendship) {
            return;
        }

        foreach ([$event->senderId, $event->receiverId] as $userId) {
            $user = User::find($userId);

            if (! $user) {
                continue;
            }

            $acceptedCount = Friendship::where('status', FriendshipStatus::Accepted)
                ->where(fn ($query) => $query->where('sender_id', $userId)->orWhere('receiver_id', $userId))
                ->count();

            if ($acceptedCount >= self::THRESHOLD) {
                $this->badges->award($user, BadgeKey::SocialButterfly, $friendship);
            }
        }
    }
}
