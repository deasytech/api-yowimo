<?php

namespace App\Exceptions\Api;

use RuntimeException;

/**
 * Thrown when a player acts on a turn that is no longer the open, current
 * turn (already completed, skipped, or timed out) — typically a stale client.
 */
class TurnNotActiveException extends RuntimeException
{
    public function __construct(string $message = 'This turn is no longer active.')
    {
        parent::__construct($message);
    }
}
