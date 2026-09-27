<?php

namespace App\Services\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class OpenAiProvider implements AIProvider
{
    public function respond(string $prompt): string
    {
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            throw new OpenAiNotConfiguredException;
        }

        $model = config('services.openai.model');

        // o-series reasoning models reject `max_tokens` and require
        // `max_completion_tokens` instead.
        $tokenLimitKey = preg_match('/^o\d/', (string) $model) ? 'max_completion_tokens' : 'max_tokens';

        try {
            $response = Http::withToken($apiKey)
                ->timeout(10)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    $tokenLimitKey => 150,
                ])
                ->throw();
        } catch (ConnectionException $e) {
            throw new AiProviderFailedException(
                'Could not reach OpenAI: '.$e->getMessage(),
                previous: $e,
            );
        } catch (RequestException $e) {
            throw new AiProviderFailedException(
                'OpenAI rejected the request: '.$e->getMessage(),
                retryable: self::isRetryable($e->response),
                previous: $e,
            );
        }

        return trim((string) $response->json('choices.0.message.content'));
    }

    /**
     * Timeouts, conflicts, rate limits and upstream 5xx responses can clear
     * on their own, so they're worth retrying; every other 4xx (bad key,
     * malformed request, exhausted quota) is deterministic and returns the
     * same error on each attempt.
     */
    private static function isRetryable(Response $response): bool
    {
        $status = $response->status();

        if ($status === 429) {
            return $response->json('error.code') !== 'insufficient_quota';
        }

        return $status === 408 || $status === 409 || $status >= 500;
    }
}
