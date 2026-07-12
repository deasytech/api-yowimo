<?php

namespace App\Exceptions\Api;

use Exception;

class InvalidClerkTokenException extends Exception
{
    public function __construct(string $message = 'Invalid or expired authentication token.')
    {
        parent::__construct($message);
    }
}
