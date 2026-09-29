<?php

namespace App\Exceptions\Api;

use RuntimeException;

/**
 * Thrown when a video token is requested before LIVEKIT_API_KEY/
 * LIVEKIT_API_SECRET/LIVEKIT_URL are configured — mirrors
 * OpenAiNotConfiguredException/PaystackNotConfiguredException's "inert until
 * set" pattern, but this one is user-facing (503) rather than swallowed,
 * since there's no manual/fallback mode for video like ManualPaymentProvider.
 */
class LiveKitNotConfiguredException extends RuntimeException
{
    public function __construct(string $message = 'Video calling is not configured.')
    {
        parent::__construct($message);
    }
}
