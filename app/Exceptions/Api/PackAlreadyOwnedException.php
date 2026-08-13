<?php

namespace App\Exceptions\Api;

use RuntimeException;

class PackAlreadyOwnedException extends RuntimeException
{
    public function __construct(string $message = 'You already own this pack.')
    {
        parent::__construct($message);
    }
}
