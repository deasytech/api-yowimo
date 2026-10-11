<?php

namespace App\Exceptions\Api;

use RuntimeException;

class SponsorshipInviteNotPendingException extends RuntimeException
{
    public function __construct(string $message = 'This sponsorship invite is no longer available.')
    {
        parent::__construct($message);
    }
}
