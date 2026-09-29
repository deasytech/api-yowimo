<?php

namespace App\Services\Paystack;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

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
     * Deactivates a saved authorization so it can no longer be charged.
     * Called (best-effort) when a saved card is removed or the account is
     * deleted — see AccountDeletionService.
     *
     * @return array<string, mixed>
     */
    public function deactivateAuthorization(string $authorizationCode): array
    {
        return $this->request('post', '/customer/deactivate_authorization', [
            'authorization_code' => $authorizationCode,
        ]);
    }

    /**
     * Best-effort wrapper around deactivateAuthorization() shared by every
     * caller that removes a saved card (AccountDeletionService,
     * PaymentMethodService): a Paystack failure is logged, never thrown, so
     * it never blocks the local removal it's attached to. Catches both a
     * thrown failure (connection/config) and a completed-but-declined
     * response (`status: false`, which never throws — see request()) so
     * neither passes silently. $context is merged into the log so the
     * caller's identifying fields (payment_method_id, user_id, ...) show up
     * without this method needing to know their shape. authorizationCode
     * itself is deliberately never logged — it's the reusable charge
     * credential PaymentMethod::$hidden already keeps out of API responses,
     * and logs (ingested by Sentry) are a broader exposure surface than this
     * app's own DB.
     *
     * @param  array{user_id?: int, payment_method_id?: int}  $context
     */
    public function deactivateAuthorizationSafely(string $authorizationCode, array $context = []): void
    {
        try {
            $response = $this->deactivateAuthorization($authorizationCode);

            if (($response['status'] ?? false) !== true) {
                Log::warning('Paystack declined to deactivate an authorization.', [
                    ...$context,
                    'response' => $response,
                ]);
            }
        } catch (Throwable $e) {
            Log::warning('Failed to deactivate a Paystack authorization.', [
                ...$context,
                'error' => $e->getMessage(),
            ]);
        }
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
     * Never throws for a *completed* request: a bad reference/authorization
     * code or a genuine card decline are routine outcomes here (a purchase
     * attempt), not exceptional ones — the caller (PaystackPaymentProvider)
     * treats any response without a successful `data.status` as a decline.
     * Paystack itself returns a non-2xx status (with no `data` key) for a
     * not-found reference or a malformed authorization code, as opposed to a
     * genuine card decline, which is a 200 with `data.status: "failed"` —
     * both are handled uniformly by returning the body rather than throwing,
     * so neither ever surfaces as a 500 to the customer.
     *
     * A connection failure is different: we genuinely don't know whether
     * Paystack processed the request before the connection dropped, so it's
     * surfaced as PaystackConnectionException rather than folded into a
     * silent decline — see PaystackPaymentProvider for how that ambiguity is
     * resolved for a charge attempt specifically.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws PaystackConnectionException
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

            throw new PaystackConnectionException($e->getMessage(), previous: $e);
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
            throw new PaystackNotConfiguredException('Paystack secret key is not configured.');
        }

        return $secretKey;
    }
}
