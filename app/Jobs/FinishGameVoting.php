<?php

namespace App\Jobs;

use App\Services\Game\GameSessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once a game's final voting window closes. No-ops if the session has
 * already moved on (completed by a previous run / the crash-recovery sweep,
 * or ended by the host) or the window hasn't actually elapsed yet.
 */
class FinishGameVoting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $gameSessionId) {}

    public function handle(GameSessionService $sessions): void
    {
        $sessions->finishVoting($this->gameSessionId);
    }
}
