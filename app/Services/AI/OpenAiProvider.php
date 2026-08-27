<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAiProvider implements AIProvider
{
    public function respond(string $prompt): string
    {
        $apiKey = config('services.openai.api_key');

        if (! $apiKey) {
            throw new RuntimeException('OpenAI API key is not configured.');
        }

        $model = config('services.openai.model');

        // o-series reasoning models reject `max_tokens` and require
        // `max_completion_tokens` instead.
        $tokenLimitKey = preg_match('/^o\d/', (string) $model) ? 'max_completion_tokens' : 'max_tokens';

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

        return trim((string) $response->json('choices.0.message.content'));
    }
}
