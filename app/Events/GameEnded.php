<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The game was stopped before its last turn (the host ended the party). No completion rewards follow.
 */
class GameEnded implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $gameSessionId,
        public readonly int $partyId,
    ) {}

    /**
     * @return array<int, PrivateChannel|PresenceChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("game-session.{$this->gameSessionId}"),
            new PresenceChannel("party.{$this->partyId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'game.ended';
    }
}
