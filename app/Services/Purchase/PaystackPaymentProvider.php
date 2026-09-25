<?php

namespace App\Services\Purchase;

use App\Models\PaymentMethod;
use App\Models\TokenBundle;
use App\Models\User;
use App\Services\Paystack\PaystackClient;
use App\Services\Paystack\PaystackConnectionException;
use Illuminate\Support\Str;

class PaystackPaymentProvider implements PaymentProvider
{
    public const PROVIDER_NAME = 'paystack';

    public function __construct(
        private readonly PaystackClient $client,
        private readonly PaymentMethodService $paymentMethods,
    ) {}

    public function charge(
        User $user,
        TokenBundle $bundle,
        ?PaymentMethod $paymentMethod = null,
        ?string $paymentReference = null,
        ?string $idempotencyKey = null,
    ): bool {
        // Resolved once per attempt: the bundle's default price/currency
        // (NGN, in practice), or USD when the buyer is confirmed to be
        // outside Nigeria and this bundle has a USD price set. This must be
        // the exact amount/currency actually charged, matching what
        // TokenBundleResource showed this same user before they purchased.
        $price = $bundle->priceFor($user);

        if ($paymentReference !== null) {
            return $this->chargeByReference($user, $paymentReference, $price);
        }

        $method = $paymentMethod ?? $this->paymentMethods->defaultFor($user);

        return $method && $this->chargeSavedMethod($user, $method, $price, $idempotencyKey);
    }

    /**
     * The client already completed this charge via Paystack's own SDK
     * (which never passes card data through our backend); we independently
     * verify it really succeeded, for the right amount and currency, and
     * that it was actually this user's charge, before crediting anything.
     *
     * @param  array{amount: float, currency: string}  $price
     */
    private function chargeByReference(User $user, string $reference, array $price): bool
    {
        try {
            $response = $this->client->verifyTransaction($reference);
        } catch (PaystackConnectionException) {
            // Verifying has no side effect, so declining and letting the
            // client retry the whole purchase call (same idempotency key,
            // same reference) is always safe here — unlike a saved-method
            // charge, there's no risk of double-charging a card by trying
            // again.
            return false;
        }

        $data = $response['data'] ?? [];

        if (! $this->matchesExpectedCharge($data, $price) || ! $this->belongsToUser($data, $user)) {
            return false;
        }

        $authorization = $data['authorization'] ?? null;

        if (($authorization['reusable'] ?? false)
            && $this->paymentMethods->saveFromAuthorization($user, self::PROVIDER_NAME, $authorization) === null) {
            // The authorization is already saved against a *different*
            // user — a real charge that doesn't belong to $user shouldn't
            // be possible once belongsToUser() has passed, but treat it as
            // declined rather than silently reassigning someone else's card.
            return false;
        }

        return true;
    }

    /**
     * @param  array{amount: float, currency: string}  $price
     */
    private function chargeSavedMethod(User $user, PaymentMethod $method, array $price, ?string $idempotencyKey): bool
    {
        $reference = $this->referenceFor($user, $idempotencyKey);

        try {
            $response = $this->client->chargeAuthorization(
                authorizationCode: $method->authorization_code,
                email: $user->email ?? "user-{$user->id}@yowimo.invalid",
                amountInSubunits: $this->toSubunits($price['amount']),
                currency: $price['currency'],
                reference: $reference,
            );
        } catch (PaystackConnectionException) {
            // Unlike verifying, this attempt may have actually reached
            // Paystack and charged the card before the connection dropped —
            // blindly retrying could double-charge it. The reference is
            // deterministic per (user, idempotency key), so verifying it
            // resolves the ambiguity: if the charge went through, this
            // confirms it without charging again; if it didn't, Paystack
            // has no record of the reference and this correctly declines.
            return $this->verifyReference($reference, $price);
        }

        return $this->matchesExpectedCharge($response['data'] ?? [], $price);
    }

    /**
     * @param  array{amount: float, currency: string}  $price
     */
    private function verifyReference(string $reference, array $price): bool
    {
        try {
            $response = $this->client->verifyTransaction($reference);
        } catch (PaystackConnectionException) {
            return false;
        }

        return $this->matchesExpectedCharge($response['data'] ?? [], $price);
    }

    /**
     * A charge_authorization reference must be unique per attempt but the
     * same across retries of that *same* attempt, so it's derived
     * deterministically from the idempotency key rather than randomized —
     * a retry then resolves to the original Paystack transaction instead of
     * charging the card again. Hashed (not used raw) since Paystack
     * constrains the reference format and the idempotency key doesn't have
     * to satisfy it; namespaced per user so two users can never collide.
     */
    private function referenceFor(User $user, ?string $idempotencyKey): string
    {
        if ($idempotencyKey === null) {
            return (string) Str::uuid();
        }

        return 'yowimo_'.$user->id.'_'.hash('sha256', $idempotencyKey);
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{amount: float, currency: string}  $price
     */
    private function matchesExpectedCharge(array $data, array $price): bool
    {
        return ($data['status'] ?? null) === 'success'
            && (int) ($data['amount'] ?? 0) === $this->toSubunits($price['amount'])
            && strtoupper((string) ($data['currency'] ?? '')) === strtoupper($price['currency']);
    }

    /**
     * A successful, correctly-priced transaction still isn't proof it's
     * *this* user's charge — a reference is just a string, and without this
     * check, submitting someone else's valid reference would credit the
     * wrong wallet. A user with no email on file can never be verified this
     * way and always declines; there's no reference-path purchase for them
     * until they have one.
     *
     * @param  array<string, mixed>  $data
     */
    private function belongsToUser(array $data, User $user): bool
    {
        $customerEmail = $data['customer']['email'] ?? null;

        return $user->email !== null
            && $customerEmail !== null
            && strcasecmp($user->email, $customerEmail) === 0;
    }

    private function toSubunits(string|float|int $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
