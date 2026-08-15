<?php

namespace App\Exceptions\Api;

use RuntimeException;

class GameSessionPackUnavailableException extends RuntimeException
{
    public function __construct(string $message = "This party's pack has no cards available to deal.")
    {
        parent::__construct($message);
    }
}
