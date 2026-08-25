<?php

namespace App\Listeners;

use App\Events\RoundCompleted;
use App\Models\GameSession;
use App\Models\PartyMember;
use App\Models\Round;
use App\Models\User;
use App\Notifications\RoundCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendRoundCompletedPushNotification implements ShouldQueue
{
    public function handle(RoundCompleted $event): void
    {
        $gameSession = GameSession::find($event->gameSessionId);
        $round = Round::find($event->roundId);

        if (! $gameSession || ! $round) {
            return;
        }

        $userIds = PartyMember::where('party_id', $gameSession->party_id)->pluck('user_id');
        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new RoundCompletedNotification($gameSession, $round));
    }
}
