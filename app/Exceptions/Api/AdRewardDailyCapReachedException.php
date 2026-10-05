<?php

namespace App\Exceptions\Api;

use RuntimeException;

class AdRewardDailyCapReachedException extends RuntimeException
{
    public function __construct(string $message = "You've reached today's ad reward limit.")
    {
        parent::__construct($message);
    }
}
