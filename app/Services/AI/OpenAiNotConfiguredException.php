<?php

namespace App\Services\AI;

/**
 * Thrown when OpenAiProvider::respond() is called without OPENAI_API_KEY
 * configured. Terminal by design: no amount of retrying conjures up
 * credentials, so listeners skip the message with a warning (the AI host
 * stays inert until the key is set).
 */
class OpenAiNotConfiguredException extends AiProviderFailedException
{
    public function __construct(string $message = 'OpenAI API key is not configured.')
    {
        parent::__construct($message, retryable: false);
    }
}
