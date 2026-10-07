<?php

namespace App\Services\Referrals;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Generates a unique, short, manually-typeable referral code.
 */
class ReferralCodeGenerator
{
    public function generate(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (User::withTrashed()->where('referral_code', $code)->exists());

        return $code;
    }
}
