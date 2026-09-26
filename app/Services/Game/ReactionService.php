<?php

namespace App\Services\Game;

use App\Events\ReactionSent;
use App\Exceptions\Api\GameSessionNotActiveException;
use App\Models\GameSession;
use App\Models\User;

class ReactionService
{
    /**
     * Emojis players can react with during a game.
     *
     * @var array<int, string>
     */
    public const EMOJIS = ['🔥', '😂', '❤️', '😱', '👏', '💀', '🤯', '👀', '✨'];

    /**
     * Broadcast a live reaction to everyone in the game. Reactions don't
     * affect gameplay and aren't stored.
     *
     * @throws GameSessionNotActiveException if the game has already finished.
     */
    public function send(User $user, GameSession $session, string $emoji): void
    {
        if (! in_array($session->status, GameSessionService::IN_PROGRESS_STATUSES, true)) {
            throw new GameSessionNotActiveException;
        }

        ReactionSent::dispatch($session->id, $user->id, $emoji, $session->currentTurn()?->id);
    }
}
