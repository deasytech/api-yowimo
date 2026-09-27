<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class TurnStarted implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $gameSessionId,
        public readonly int $roundId,
        public readonly int $turnId,
        public readonly int $userId,
        public readonly int $position,
        public readonly ?string $expiresAt = null,
        /** @var array{id: int, kind: string, text: string, position: int}|null */
        public readonly ?array $card = null,
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
        return 'turn.started';
    }
}
