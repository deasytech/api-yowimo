<?php

namespace App\Exceptions\Api;

use RuntimeException;

class InvalidPartyTransitionException extends RuntimeException
{
    public function __construct(string $message = 'This action cannot be performed on the party in its current status.')
    {
        parent::__construct($message);
    }
}
