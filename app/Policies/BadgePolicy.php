<?php

namespace App\Policies;

class BadgePolicy
{
    /**
     * Determine whether the user can view any badges. Badges are public reference data.
     */
    public function viewAny(): bool
    {
        return true;
    }
}
