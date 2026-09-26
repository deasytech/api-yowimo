<?php

namespace App\Exceptions\Api;

use RuntimeException;

/**
 * Thrown when an interaction is attempted between two users where either one
 * has blocked the other. The message deliberately doesn't say which side
 * placed the block.
 */
class UserBlockedException extends RuntimeException
{
    public function __construct(string $message = 'You cannot send a friend request to this user.')
    {
        parent::__construct($message);
    }
}
