<?php

namespace App\Exceptions\Api;

use RuntimeException;

class SponsorshipScopeMismatchException extends RuntimeException
{
    public function __construct(string $message = 'This party was not set up for that sponsorship scope.')
    {
        parent::__construct($message);
    }
}
