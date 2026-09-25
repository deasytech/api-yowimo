<?php

namespace App\Policies;

use App\Models\TokenBundle;
use App\Models\User;

class TokenBundlePolicy
{
    /**
     * Determine whether the user can view any token bundles. Bundles are public store listings.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the token bundle.
     */
    public function view(?User $user, TokenBundle $tokenBundle): bool
    {
        return $tokenBundle->is_active;
    }

    /**
     * Determine whether the user can purchase the token bundle.
     */
    public function purchase(User $user, TokenBundle $tokenBundle): bool // NOSONAR php:S1172 - Laravel's Gate always calls policy methods with $user first positionally; removing it would shift $tokenBundle into that slot.
    {
        return $tokenBundle->is_active;
    }
}
