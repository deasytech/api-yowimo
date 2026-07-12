<?php

namespace Tests\Support;

use Illuminate\Testing\TestResponse;
use Svix\Webhook;

trait FakesClerkWebhook
{
    protected string $clerkWebhookSecret = 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';

    protected function fakeClerkWebhookSecret(): void
    {
        config(['services.clerk.webhook_secret' => $this->clerkWebhookSecret]);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{id: string, headers: array<string, string>, body: string}
     */
    protected function signedClerkWebhook(array $payload, ?string $svixId = null): array
    {
        $svixId = $svixId ?? 'msg_'.str()->random(20);
        $timestamp = (string) time();
        $body = json_encode($payload);

        $signature = (new Webhook($this->clerkWebhookSecret))->sign($svixId, $timestamp, $body);

        return [
            'id' => $svixId,
            'body' => $body,
            'headers' => [
                'svix-id' => $svixId,
                'svix-timestamp' => $timestamp,
                'svix-signature' => $signature,
            ],
        ];
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function postClerkWebhook(string $body, array $headers): TestResponse
    {
        return $this->call('POST', '/api/v1/webhooks/clerk', server: $this->transformHeadersToServerVars($headers), content: $body);
    }
}
