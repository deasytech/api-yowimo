<?php

namespace App\Services\Paystack;

use RuntimeException;

/**
 * Thrown when a Paystack API call couldn't be completed at all (timeout,
 * DNS, TLS, etc.), as opposed to a completed request that Paystack itself
 * declined or rejected. The distinction matters to PaystackPaymentProvider:
 * a business decline (invalid reference, failed charge) is a known outcome,
 * but a connection failure during a charge attempt is genuinely ambiguous —
 * the card may or may not have actually been charged — so it's handled
 * differently (verify before assuming declined) rather than folded into a
 * plain `false`.
 */
class PaystackConnectionException extends RuntimeException
{
    //
}
