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
}
