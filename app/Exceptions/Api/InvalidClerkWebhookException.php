<?php

namespace App\Exceptions\Api;

use Exception;

class InvalidClerkWebhookException extends Exception
{
    public function __construct(string $message = 'Invalid webhook signature.')
    {
        parent::__construct($message);
    }
}
