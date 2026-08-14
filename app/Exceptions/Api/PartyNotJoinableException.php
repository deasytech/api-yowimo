<?php

namespace App\Exceptions\Api;

use RuntimeException;

class PartyNotJoinableException extends RuntimeException
{
    public function __construct(string $message = 'This party cannot be joined right now.')
    {
        parent::__construct($message);
    }
}
