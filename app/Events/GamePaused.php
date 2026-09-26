<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The host paused the game; the current turn timer is frozen with `turnRemainingSeconds` left.
 */
class GamePaused implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $gameSessionId,
        public readonly ?int $turnRemainingSeconds,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("game-session.{$this->gameSessionId}")];
    }

    public function broadcastAs(): string
    {
        return 'game.paused';
    }
}
