<?php

namespace App\Exceptions\Api;

use RuntimeException;

class ReferralAlreadyClaimedException extends RuntimeException
{
    public function __construct(string $message = "You've already claimed a referral code.")
    {
        parent::__construct($message);
    }
}
