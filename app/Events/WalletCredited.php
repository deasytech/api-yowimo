<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class WalletCredited implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $walletTransactionId,
        public readonly int $userId,
    ) {}
}
