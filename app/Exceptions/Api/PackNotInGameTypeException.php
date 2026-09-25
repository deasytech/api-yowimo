<?php

namespace App\Exceptions\Api;

use RuntimeException;

class PackNotInGameTypeException extends RuntimeException
{
    public function __construct(string $message = 'The selected pack does not belong to the selected game type.')
    {
        parent::__construct($message);
    }
}
