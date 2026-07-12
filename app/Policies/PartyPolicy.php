<?php

namespace App\Policies;

use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Models\Party;
use App\Models\User;

class PartyPolicy
{
    /**
     * Determine whether the user can browse the public discover feed.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the party. Public parties are visible to anyone;
     * private parties and drafts are only visible to their host.
     */
    public function view(?User $user, Party $party): bool
    {
        if ($user && $party->host_id === $user->id) {
            return true;
        }

        if ($party->status === PartyStatus::Draft) {
            return false;
        }

        return $party->visibility === PartyVisibility::Public;
    }

    /**
     * Determine whether the user can create a party.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can like the party. Only parties the user can view are likeable.
     */
    public function like(User $user, Party $party): bool
    {
        return $this->view($user, $party);
    }

    /**
     * Determine whether the user can remove their like from the party.
     */
    public function unlike(User $user, Party $party): bool
    {
        return $this->view($user, $party);
    }
}
