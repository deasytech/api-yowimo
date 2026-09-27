<?php

namespace App\Services\AI;

use RuntimeException;
use Throwable;

/**
 * Thrown when an AI provider fails to produce a response.
 *
 * `retryable` tells the queued listeners whether re-sending the same prompt
 * could plausibly succeed: transient upstream troubles (timeouts, connection
 * errors, rate limits, 5xx) are worth the listener's retry/backoff, whereas
 * terminal ones (missing credentials, exhausted quota, rejected request)
 * fail identically on every attempt and are skipped with a single warning
 * instead of burning retries and logging an error per attempt.
 */
class AiProviderFailedException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly bool $retryable = true,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
