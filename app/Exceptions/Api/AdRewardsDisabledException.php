<?php

namespace App\Exceptions\Api;

use RuntimeException;

class AdRewardsDisabledException extends RuntimeException
{
    public function __construct(string $message = 'Rewarded ads are not available right now.')
    {
        parent::__construct($message);
    }
}
