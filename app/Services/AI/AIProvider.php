<?php

namespace App\Services\AI;

interface AIProvider
{
    /**
     * Get a single text completion for the given prompt.
     *
     * @throws \Throwable if the provider fails to produce a response.
     */
    public function respond(string $prompt): string;
}
