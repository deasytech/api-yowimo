<?php

use App\Services\AI\AiProviderFailedException;
use App\Services\AI\OpenAiNotConfiguredException;
use App\Services\AI\OpenAiProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

const OPENAI_CHAT_COMPLETIONS_URL = 'https://api.openai.com/v1/chat/completions';

beforeEach(function () {
    config([
        'services.openai.api_key' => 'sk_test_fake',
        'services.openai.model' => 'gpt-4o-mini',
    ]);
});

/**
 * Run a completion against a faked OpenAI response and return the failure the
 * provider translated it into.
 *
 * @param  array<string, mixed>  $body
 */
function openAiFailureFor(int $status, array $body = []): AiProviderFailedException
{
    Http::fake([OPENAI_CHAT_COMPLETIONS_URL => Http::response($body, $status)]);

    try {
        app(OpenAiProvider::class)->respond('Say something witty.');
    } catch (AiProviderFailedException $e) {
        return $e;
    }

    throw new RuntimeException('The AI provider was expected to fail, but it did not.');
}

it('returns the trimmed completion text', function () {
    Http::fake([
        OPENAI_CHAT_COMPLETIONS_URL => Http::response([
            'choices' => [['message' => ['content' => "  Let's go, party people!  "]]],
        ]),
    ]);

    expect(app(OpenAiProvider::class)->respond('Say something witty.'))
        ->toBe("Let's go, party people!");
});

it('fails terminally when no API key is configured', function () {
    config(['services.openai.api_key' => null]);

    try {
        app(OpenAiProvider::class)->respond('Say something witty.');
    } catch (OpenAiNotConfiguredException $e) {
        expect($e->retryable)->toBeFalse();

        return;
    }

    throw new RuntimeException('The AI provider was expected to fail, but it did not.');
});

it('fails terminally when the OpenAI account is out of credits', function () {
    $failure = openAiFailureFor(429, [
        'error' => ['code' => 'insufficient_quota', 'message' => 'You have no credits remaining.'],
    ]);

    expect($failure->retryable)->toBeFalse();
});

it('fails terminally when OpenAI rejects the request', function (int $status) {
    expect(openAiFailureFor($status, ['error' => ['code' => 'invalid_api_key']])->retryable)->toBeFalse();
})->with([400, 401, 403, 404, 422]);

it('fails retryably on a rate limit or a server error', function (int $status) {
    expect(openAiFailureFor($status)->retryable)->toBeTrue();
})->with([408, 429, 500, 502, 503]);

it('fails retryably when OpenAI cannot be reached', function () {
    Http::fake(fn () => throw new ConnectionException('cURL error 56: OpenSSL SSL_read'));

    try {
        app(OpenAiProvider::class)->respond('Say something witty.');
    } catch (AiProviderFailedException $e) {
        expect($e->retryable)->toBeTrue();

        return;
    }

    throw new RuntimeException('The AI provider was expected to fail, but it did not.');
});
