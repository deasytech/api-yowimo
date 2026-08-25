<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class GameCompleted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $gameSessionId,
        public readonly int $partyId,
    ) {}

    /**
     * Broadcast on both the game session (for players mid-game) and the party
     * lobby (so the lobby view reflects that play has ended) channels.
     *
     * @return array<int, Channel>
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
        return 'game.completed';
    }
}
