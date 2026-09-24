<?php

namespace App\Services\Paystack;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Lean HTTP-facade wrapper around the Paystack REST API (no SDK package),
 * mirroring how OpenAiProvider calls OpenAI directly.
 */
class PaystackClient
{
    /**
     * @return array<string, mixed>
     */
    public function verifyTransaction(string $reference): array
    {
        return $this->request('get', '/transaction/verify/'.rawurlencode($reference));
    }

    /**
     * @return array<string, mixed>
     */
    public function chargeAuthorization(
        string $authorizationCode,
        string $email,
        int $amountInSubunits,
        string $currency,
        string $reference,
    ): array {
        return $this->request('post', '/transaction/charge_authorization', [
            'authorization_code' => $authorizationCode,
            'email' => $email,
            'amount' => $amountInSubunits,
            'currency' => strtoupper($currency),
            'reference' => $reference,
        ]);
    }

    /**
     * Verifies the `x-paystack-signature` header: HMAC-SHA512 of the raw
     * request body, keyed with the secret key.
     */
    public function verifyWebhookSignature(string $rawBody, ?string $signature): bool
    {
        $secretKey = $this->secretKey();

        if (! $signature) {
            return false;
        }

        return hash_equals(hash_hmac('sha512', $rawBody, $secretKey), $signature);
    }

    /**
     * Never throws: a bad reference/authorization code, a card decline, or a
     * network failure are all routine outcomes here (a purchase attempt),
     * not exceptional ones — the caller (PaystackPaymentProvider) treats any
     * response without a successful `data.status` as a decline. Paystack
     * itself returns a non-2xx status (with no `data` key) for a not-found
     * reference or a malformed authorization code, as opposed to a genuine
     * card decline, which is a 200 with `data.status: "failed"` — both are
     * handled uniformly by returning the body (or an empty array) rather
     * than throwing, so neither ever surfaces as a 500 to the customer.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        try {
            $response = Http::withToken($this->secretKey())
                ->baseUrl(config('services.paystack.base_url', 'https://api.paystack.co'))
                ->timeout(15)
                ->{$method}($path, $payload);
        } catch (ConnectionException $e) {
            Log::warning('Paystack request failed to connect.', ['path' => $path, 'error' => $e->getMessage()]);

            return [];
        }

        if ($response->failed()) {
            Log::warning('Paystack request returned an error response.', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->json() ?? $response->body(),
            ]);
        }

        return $response->json() ?? [];
    }

    private function secretKey(): string
    {
        $secretKey = config('services.paystack.secret_key');

        if (! $secretKey) {
            throw new RuntimeException('Paystack secret key is not configured.');
        }

        return $secretKey;
    }
}
