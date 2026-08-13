<?php

namespace App\Services\Purchase;

use App\Models\TokenBundle;
use App\Models\User;

interface PaymentProvider
{
    /**
     * Attempt to charge the user for the given token bundle. Returns whether
     * the charge was approved; a real provider (Stripe/Paystack, later) may
     * decline, whereas the manual/test driver always approves.
     */
    public function charge(User $user, TokenBundle $bundle): bool;
}
