<?php

namespace App\Listeners;

use App\Events\PartyStarted;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Notifications\PartyStartedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendPartyStartedPushNotification implements ShouldQueue
{
    public function handle(PartyStarted $event): void
    {
        $party = Party::find($event->partyId);

        if (! $party) {
            return;
        }

        $userIds = PartyMember::where('party_id', $party->id)
            ->where('user_id', '!=', $party->host_id)
            ->pluck('user_id');
        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return;
        }

        Notification::send($users, new PartyStartedNotification($party));
    }
}
