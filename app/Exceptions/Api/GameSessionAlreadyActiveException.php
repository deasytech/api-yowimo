<?php

namespace App\Exceptions\Api;

use RuntimeException;

class GameSessionAlreadyActiveException extends RuntimeException
{
    /**
     * @param  int|null  $gameSessionId  the session already in progress, so the client can open it instead
     */
    public function __construct(
        string $message = 'This party already has an active game session.',
        public readonly ?int $gameSessionId = null,
    ) {
        parent::__construct($message);
    }
}
