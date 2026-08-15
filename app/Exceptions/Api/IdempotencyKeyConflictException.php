<?php

namespace App\Exceptions\Api;

use RuntimeException;

class IdempotencyKeyConflictException extends RuntimeException
{
    public function __construct(string $message = 'This idempotency key was already used for a different operation.')
    {
        parent::__construct($message);
    }
}
