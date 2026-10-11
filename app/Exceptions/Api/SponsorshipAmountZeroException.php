<?php

namespace App\Exceptions\Api;

use RuntimeException;

class SponsorshipAmountZeroException extends RuntimeException
{
    public function __construct(string $message = 'This party has nothing to sponsor.')
    {
        parent::__construct($message);
    }
}
