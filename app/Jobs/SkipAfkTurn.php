<?php

namespace App\Jobs;

use App\Services\Game\GameSessionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once a turn's timer expires. No-ops if the turn was already completed
 * (by the host advancing normally, or by a previous run of this job / the
 * crash-recovery sweep) by the time it runs.
 */
class SkipAfkTurn implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $turnId) {}

    public function handle(GameSessionService $sessions): void
    {
        $sessions->skipAfkTurn($this->turnId);
    }
}
