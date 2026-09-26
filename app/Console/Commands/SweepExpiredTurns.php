<?php

namespace App\Console\Commands;

use App\Services\Game\GameSessionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game:sweep-expired-turns')]
#[Description('Crash-recovery safety net: AFK-skip expired turns and close expired final voting windows whose delayed queue jobs never ran.')]
class SweepExpiredTurns extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GameSessionService $sessions): int
    {
        $skipped = $sessions->sweepExpiredTurns();
        $completed = $sessions->sweepExpiredVotingWindows();

        $this->info("Swept {$skipped} expired turn(s) and {$completed} expired voting window(s).");

        return self::SUCCESS;
    }
}
