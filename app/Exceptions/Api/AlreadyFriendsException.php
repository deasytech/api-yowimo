<?php

namespace App\Exceptions\Api;

use RuntimeException;

class AlreadyFriendsException extends RuntimeException
{
    public function __construct(string $message = 'These users are already friends.')
    {
        parent::__construct($message);
    }
}
