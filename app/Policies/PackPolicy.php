<?php

namespace App\Policies;

use App\Models\Pack;
use App\Models\User;

class PackPolicy
{
    /**
     * Determine whether the user can view any packs. Packs are public marketplace listings.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the pack.
     */
    public function view(?User $user, Pack $pack): bool
    {
        return $pack->is_active;
    }
}
