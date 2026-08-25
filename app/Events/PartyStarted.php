<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class PartyStarted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $partyId,
    ) {}

    /**
     * @return array<int, PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [new PresenceChannel("party.{$this->partyId}")];
    }

    public function broadcastAs(): string
    {
        return 'party.started';
    }
}
