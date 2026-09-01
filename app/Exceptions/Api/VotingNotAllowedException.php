<?php

namespace App\Exceptions\Api;

use RuntimeException;

class VotingNotAllowedException extends RuntimeException
{
    public function __construct(string $message = 'Voting is not allowed on this turn.')
    {
        parent::__construct($message);
    }
}
