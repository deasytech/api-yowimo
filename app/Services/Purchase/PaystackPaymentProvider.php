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
        $data = $this->verifyTransactionData($reference);

        if ($data === null || ! $this->matchesExpectedCharge($data, $price) || ! $this->belongsToUser($data, $user)) {
            return false;
        }

        return $this->saveReusableAuthorizationIfAny($user, $data);
    }

    /**
     * Saves a reusable authorization from a verified charge as a new
     * payment method, if the transaction returned one. Declines (rather
     * than crediting anyway) if that authorization is already saved to a
     * *different* user — a real charge that doesn't belong to $user
     * shouldn't be possible once belongsToUser() has passed, but this
     * refuses to silently reassign someone else's card either way.
     *
     * @param  array<string, mixed>  $data
     */
    private function saveReusableAuthorizationIfAny(User $user, array $data): bool
    {
        $authorization = $data['authorization'] ?? null;

        if (! ($authorization['reusable'] ?? false)) {
            return true;
        }

        return $this->paymentMethods->saveFromAuthorization($user, self::PROVIDER_NAME, $authorization) !== null;
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

        if ($this->matchesExpectedCharge($response['data'] ?? [], $price)) {
            return true;
        }

        // The synchronous response didn't confirm success outright (e.g. a
        // non-final status rather than a definitive decline) — the
        // reference is deterministic for this attempt, so verifying it
        // resolves whether it actually completed instead of assuming
        // decline from a response that may not be the final word.
        return $this->verifyReference($reference, $price);
    }

    /**
     * @param  array{amount: float, currency: string}  $price
     */
    private function verifyReference(string $reference, array $price): bool
    {
        $data = $this->verifyTransactionData($reference);

        return $data !== null && $this->matchesExpectedCharge($data, $price);
    }

    /**
     * Verifies a transaction and returns its `data`, or null if the
     * connection to Paystack itself failed — as opposed to a completed
     * verification of a not-found/failed transaction, which still returns
     * an array (possibly empty). Declining and letting the client retry the
     * whole purchase call is always safe on a connection failure here,
     * since verifying has no side effect.
     *
     * @return array<string, mixed>|null
     */
    private function verifyTransactionData(string $reference): ?array
    {
        try {
            $response = $this->client->verifyTransaction($reference);
        } catch (PaystackConnectionException) {
            return null;
        }

        return $response['data'] ?? [];
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
