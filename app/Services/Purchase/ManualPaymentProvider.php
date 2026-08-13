<?php

namespace App\Services\Purchase;

use App\Models\TokenBundle;
use App\Models\User;

/**
 * Stand-in payment driver used until a real payment gateway (Stripe/Paystack)
 * is integrated. Always approves; no money actually changes hands.
 */
class ManualPaymentProvider implements PaymentProvider
{
    public function charge(User $user, TokenBundle $bundle): bool
    {
        return true;
    }
}
