<?php

namespace App\Services\Purchase;

use App\Models\PaymentMethod;
use App\Models\TokenBundle;
use App\Models\User;
use App\Services\Paystack\PaystackClient;
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
    ): bool {
        if ($paymentReference !== null) {
            return $this->chargeByReference($user, $paymentReference, $bundle);
        }

        $method = $paymentMethod ?? $this->paymentMethods->defaultFor($user);

        return $method && $this->chargeSavedMethod($user, $bundle, $method);
    }

    /**
     * The client already completed this charge via Paystack's own SDK
     * (which never passes card data through our backend); we independently
     * verify it really succeeded, for the right amount and currency,
     * before crediting anything.
     */
    private function chargeByReference(User $user, string $reference, TokenBundle $bundle): bool
    {
        $response = $this->client->verifyTransaction($reference);
        $data = $response['data'] ?? [];

        if (! $this->matchesExpectedCharge($data, $bundle)) {
            return false;
        }

        $authorization = $data['authorization'] ?? null;

        if ($authorization['reusable'] ?? false) {
            $this->paymentMethods->saveFromAuthorization($user, self::PROVIDER_NAME, $authorization);
        }

        return true;
    }

    private function chargeSavedMethod(User $user, TokenBundle $bundle, PaymentMethod $method): bool
    {
        $response = $this->client->chargeAuthorization(
            authorizationCode: $method->authorization_code,
            email: $user->email ?? "user-{$user->id}@yowimo.invalid",
            amountInSubunits: $this->toSubunits($bundle->price),
            currency: $bundle->currency,
            reference: (string) Str::uuid(),
        );

        return $this->matchesExpectedCharge($response['data'] ?? [], $bundle);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function matchesExpectedCharge(array $data, TokenBundle $bundle): bool
    {
        return ($data['status'] ?? null) === 'success'
            && (int) ($data['amount'] ?? 0) === $this->toSubunits($bundle->price)
            && strtoupper((string) ($data['currency'] ?? '')) === strtoupper($bundle->currency);
    }

    private function toSubunits(string|float|int $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
