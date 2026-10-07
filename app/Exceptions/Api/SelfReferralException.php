<?php

namespace App\Exceptions\Api;

use RuntimeException;

class SelfReferralException extends RuntimeException
{
    public function __construct(string $message = 'You cannot claim your own referral code.')
    {
        parent::__construct($message);
    }
}
