<?php

namespace App\Services\Paystack;

use RuntimeException;

/**
 * Thrown when a Paystack API call is attempted without PAYSTACK_SECRET_KEY
 * configured. Should be unreachable in practice: AppServiceProvider only
 * binds PaystackPaymentProvider (the only thing that constructs a
 * PaystackClient) when that key is present.
 */
class PaystackNotConfiguredException extends RuntimeException
{
    //
}
