<?php

namespace App\Exceptions\Api;

use RuntimeException;

class InsufficientWalletBalanceException extends RuntimeException
{
    public function __construct(string $message = 'Insufficient token balance.')
    {
        parent::__construct($message);
    }
}
