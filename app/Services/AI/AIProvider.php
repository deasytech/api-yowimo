<?php

namespace App\Services\AI;

interface AIProvider
{
    /**
     * Get a single text completion for the given prompt.
     *
     * @throws AiProviderFailedException if the provider fails to produce a
     *                                   response; its `retryable` flag says whether re-sending the same
     *                                   prompt could plausibly succeed.
     */
    public function respond(string $prompt): string;
}
