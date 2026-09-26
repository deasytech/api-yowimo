<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * The host resumed the game; the current turn (if still open) now expires at `turnExpiresAt`.
 */
class GameResumed implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $gameSessionId,
        public readonly ?int $turnId,
        public readonly ?string $turnExpiresAt,
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
        return 'game.resumed';
    }
}
