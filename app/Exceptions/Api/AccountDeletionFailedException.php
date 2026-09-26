<?php

namespace App\Exceptions\Api;

use RuntimeException;

/**
 * Thrown when the user's Clerk account couldn't be deleted (Clerk not
 * configured, unreachable, or returned an error). Nothing is changed locally
 * in that case, so the request is safe to retry.
 */
class AccountDeletionFailedException extends RuntimeException
{
    public function __construct(string $message = 'Your account could not be deleted right now. Please try again.')
    {
        parent::__construct($message);
    }
}
