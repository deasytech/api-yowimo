<?php

namespace App\Listeners;

use App\Enums\PartyMemberStatus;
use App\Events\GameCompleted;
use App\Models\GameSession;
use App\Models\PartyMember;
use App\Models\User;
use App\Notifications\GameCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendGameCompletedPushNotification implements ShouldQueue
{
    public function handle(GameCompleted $event): void
    {
        $gameSession = GameSession::find($event->gameSessionId);

        if (! $gameSession) {
            return;
        }

        $userIds = PartyMember::where('party_id', $gameSession->party_id)
            ->where('status', PartyMemberStatus::Active)
            ->pluck('user_id');
        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new GameCompletedNotification($gameSession));
    }
}
