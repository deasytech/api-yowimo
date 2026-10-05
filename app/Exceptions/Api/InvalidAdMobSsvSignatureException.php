<?php

namespace App\Exceptions\Api;

use RuntimeException;

class InvalidAdMobSsvSignatureException extends RuntimeException
{
    public function __construct(string $message = 'Invalid AdMob SSV callback signature.')
    {
        parent::__construct($message);
    }
}
