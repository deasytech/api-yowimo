<?php

namespace App\Services\Purchase;

use App\Models\PaymentMethod;
use App\Models\TokenBundle;
use App\Models\User;

/**
 * Stand-in payment driver used until a real payment gateway (Paystack) is
 * configured. Always approves; no money actually changes hands, and no
 * payment method is ever saved from it.
 */
class ManualPaymentProvider implements PaymentProvider
{
    public function charge(
        User $user,
        TokenBundle $bundle,
        ?PaymentMethod $paymentMethod = null,
        ?string $paymentReference = null,
    ): bool {
        return true;
    }
}
