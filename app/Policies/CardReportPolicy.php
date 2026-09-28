<?php

namespace App\Policies;

use App\Models\User;

class CardReportPolicy
{
    /**
     * Determine whether the user can report a card. Any authenticated user.
     */
    public function create(User $user): bool
    {
        return true;
    }
}
