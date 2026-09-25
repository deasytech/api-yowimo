<?php

namespace App\Services\AI;

use RuntimeException;

/**
 * Thrown when OpenAiProvider::respond() is called without OPENAI_API_KEY
 * configured.
 */
class OpenAiNotConfiguredException extends RuntimeException
{
    //
}
