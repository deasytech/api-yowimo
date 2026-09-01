<?php

namespace App\Exceptions\Api;

use RuntimeException;

class DuplicateVoteException extends RuntimeException
{
    public function __construct(string $message = 'You have already cast this vote on this turn.')
    {
        parent::__construct($message);
    }
}
