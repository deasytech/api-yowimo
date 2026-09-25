<?php

namespace App\Exceptions\Api;

use RuntimeException;

class PartyGameAlreadyStartedException extends RuntimeException
{
    public function __construct(string $message = 'The game type and pack can no longer be changed once a game session has started for this party.')
    {
        parent::__construct($message);
    }
}
