<?php

namespace App\Listeners;

use App\Events\PartyCreated;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class RecordAnalyticsEvent implements ShouldQueue
{
    public function handle(PartyCreated $event): void
    {
        Log::info('analytics.event_recorded', [
            'event' => PartyCreated::class,
            'party_id' => $event->partyId,
            'host_id' => $event->hostId,
        ]);
    }
}
