<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class FriendRequestAccepted implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $friendshipId,
        public readonly int $senderId,
        public readonly int $receiverId,
    ) {}
}
