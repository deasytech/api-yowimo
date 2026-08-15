<?php

namespace App\Exceptions\Api;

use RuntimeException;

class GameSessionAlreadyActiveException extends RuntimeException
{
    public function __construct(string $message = 'This party already has an active game session.')
    {
        parent::__construct($message);
    }
}
