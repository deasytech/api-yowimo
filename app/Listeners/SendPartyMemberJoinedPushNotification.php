<?php

namespace App\Listeners;

use App\Events\PartyMemberJoined;
use App\Models\Party;
use App\Models\User;
use App\Notifications\PartyMemberJoinedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPartyMemberJoinedPushNotification implements ShouldQueue
{
    public function handle(PartyMemberJoined $event): void
    {
        $party = Party::find($event->partyId);

        if (! $party || $party->host_id === $event->userId) {
            return;
        }

        $joiningUser = User::find($event->userId);
        $host = $party->host;

        if (! $joiningUser || ! $host) {
            return;
        }

        $host->notify(new PartyMemberJoinedNotification($party, $joiningUser));
    }
}
