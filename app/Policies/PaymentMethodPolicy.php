<?php

namespace App\Policies;

use App\Models\PaymentMethod;
use App\Models\User;

class PaymentMethodPolicy
{
    /**
     * Determine whether the user can view/manage the payment method. A user
     * may only act on their own.
     */
    public function manage(User $user, PaymentMethod $paymentMethod): bool
    {
        return $user->id === $paymentMethod->user_id;
    }
}
