<?php

namespace App\Listeners;

use App\Events\PartyMemberLeft;
use App\Models\Party;
use App\Models\User;
use App\Notifications\PartyMemberLeftNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPartyMemberLeftPushNotification implements ShouldQueue
{
    public function handle(PartyMemberLeft $event): void
    {
        $party = Party::find($event->partyId);

        if (! $party) {
            return;
        }

        $leavingUser = User::find($event->userId);
        $host = $party->host;

        if (! $leavingUser || ! $host) {
            return;
        }

        $host->notify(new PartyMemberLeftNotification($party, $leavingUser));
    }
}
