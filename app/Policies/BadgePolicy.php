<?php

namespace App\Policies;

use App\Models\User;

class BadgePolicy
{
    /**
     * Determine whether the user can view any badges. Badges are public reference data.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }
}
