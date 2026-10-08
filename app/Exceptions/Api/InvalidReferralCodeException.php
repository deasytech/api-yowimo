<?php

namespace App\Exceptions\Api;

use RuntimeException;

class InvalidReferralCodeException extends RuntimeException
{
    public function __construct(string $message = 'This referral code is not valid.')
    {
        parent::__construct($message);
    }
}
