<?php

namespace App\Events;

use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Also sent on the party channel, so members still in the lobby learn the new session's id.
 */
class GameStarted implements ShouldBroadcast, ShouldDispatchAfterCommit
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
        return 'game.started';
    }
}
