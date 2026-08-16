<?php

namespace App\Console\Commands;

use App\Services\Game\GameSessionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('game:sweep-expired-turns')]
#[Description('Crash-recovery safety net: AFK-skip any turn whose timer expired but whose delayed queue job never ran.')]
class SweepExpiredTurns extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(GameSessionService $sessions): int
    {
        $skipped = $sessions->sweepExpiredTurns();

        $this->info("Swept {$skipped} expired turn(s).");

        return self::SUCCESS;
    }
}
