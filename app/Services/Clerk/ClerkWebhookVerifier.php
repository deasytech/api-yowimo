<?php

namespace App\Services\Clerk;

use App\Exceptions\Api\InvalidClerkWebhookException;
use Illuminate\Http\Request;
use Svix\Webhook;
use Throwable;

class ClerkWebhookVerifier
{
    /**
     * Verify a Clerk (Svix-signed) webhook request and return its decoded payload.
     *
     * @return array<string, mixed>
     *
     * @throws InvalidClerkWebhookException
     */
    public function verify(Request $request): array
    {
        $secret = config('services.clerk.webhook_secret');

        if (! $secret) {
            throw new InvalidClerkWebhookException('Clerk webhook secret is not configured.');
        }

        $headers = [
            'svix-id' => $request->header('svix-id'),
            'svix-timestamp' => $request->header('svix-timestamp'),
            'svix-signature' => $request->header('svix-signature'),
        ];

        if (in_array(null, $headers, true)) {
            throw new InvalidClerkWebhookException('Missing required Svix headers.');
        }

        try {
            return (new Webhook($secret))->verify($request->getContent(), $headers);
        } catch (Throwable) {
            throw new InvalidClerkWebhookException('Webhook signature verification failed.');
        }
    }
}
