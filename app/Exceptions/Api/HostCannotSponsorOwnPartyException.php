<?php

namespace App\Exceptions\Api;

use RuntimeException;

class HostCannotSponsorOwnPartyException extends RuntimeException
{
    public function __construct(string $message = "You can't sponsor your own party.")
    {
        parent::__construct($message);
    }
}
