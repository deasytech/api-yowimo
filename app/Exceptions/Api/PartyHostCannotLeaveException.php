<?php

namespace App\Exceptions\Api;

use RuntimeException;

class PartyHostCannotLeaveException extends RuntimeException
{
    public function __construct(string $message = 'The host cannot leave their own party. End the party instead.')
    {
        parent::__construct($message);
    }
}
