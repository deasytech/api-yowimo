<?php

namespace App\Exceptions\Api;

use RuntimeException;

class PaymentDeclinedException extends RuntimeException
{
    public function __construct(string $message = 'Payment was declined.')
    {
        parent::__construct($message);
    }
}
