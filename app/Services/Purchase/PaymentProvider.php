<?php

namespace App\Services\Purchase;

use App\Models\PaymentMethod;
use App\Models\TokenBundle;
use App\Models\User;

interface PaymentProvider
{
    /**
     * Charge the user for the given token bundle's price. Returns whether
     * the charge was approved.
     *
     * Exactly one of $paymentMethod (a previously saved card) or
     * $paymentReference (a reference from a charge the client already
     * completed via the provider's own SDK) is normally given; if neither
     * is given, the provider may fall back to the user's default saved
     * method, or decline. The manual/test driver always approves,
     * regardless of what's given.
     *
     * If the charge was made against a fresh reference and the provider
     * returned a reusable authorization for it, the implementation is
     * responsible for saving it as a new payment method itself (e.g. via
     * PaymentMethodService) — callers only need the pass/fail result.
     */
    public function charge(
        User $user,
        TokenBundle $bundle,
        ?PaymentMethod $paymentMethod = null,
        ?string $paymentReference = null,
    ): bool;
}
