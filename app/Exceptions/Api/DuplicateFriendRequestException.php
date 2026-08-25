<?php

namespace App\Exceptions\Api;

use RuntimeException;

class DuplicateFriendRequestException extends RuntimeException
{
    public function __construct(string $message = 'A pending friend request already exists between these users.')
    {
        parent::__construct($message);
    }
}
