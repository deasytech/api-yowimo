<?php

namespace App\Listeners;

use App\Events\BadgeAwarded;
use App\Models\Badge;
use App\Models\User;
use App\Notifications\BadgeAwardedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendBadgeAwardedNotification implements ShouldQueue
{
    public function handle(BadgeAwarded $event): void
    {
        $user = User::find($event->userId);
        $badge = Badge::find($event->badgeId);

        if (! $user || ! $badge) {
            return;
        }

        $user->notify(new BadgeAwardedNotification($badge));
    }
}
