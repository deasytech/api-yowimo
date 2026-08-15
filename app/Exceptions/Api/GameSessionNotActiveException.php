<?php

namespace App\Exceptions\Api;

use RuntimeException;

class GameSessionNotActiveException extends RuntimeException
{
    public function __construct(string $message = 'This game session has already ended.')
    {
        parent::__construct($message);
    }
}
