<?php

namespace App\Exceptions\Api;

use RuntimeException;

class DuplicatePaymentReferenceException extends RuntimeException
{
    public function __construct(string $message = 'This payment reference has already been used.')
    {
        parent::__construct($message);
    }
}
