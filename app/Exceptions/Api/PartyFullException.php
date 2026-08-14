<?php

namespace App\Exceptions\Api;

use RuntimeException;

class PartyFullException extends RuntimeException
{
    public function __construct(string $message = 'This party is full.')
    {
        parent::__construct($message);
    }
}
