<?php

namespace App\Exceptions\Api;

use RuntimeException;

class InvalidFriendshipTransitionException extends RuntimeException
{
    public function __construct(string $message = 'This action cannot be performed on the friendship in its current status.')
    {
        parent::__construct($message);
    }
}
