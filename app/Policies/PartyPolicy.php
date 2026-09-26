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
     * Determine whether the user can view the party. The host and any
     * current (active) member can always view it, regardless of
     * visibility — otherwise a member of a private party couldn't reopen
     * its lobby. Anyone else can only view a non-draft public party.
     */
    public function view(?User $user, Party $party): bool
    {
        if (($user && $party->host_id === $user->id) || $party->isMemberOf($user)) {
            return true;
        }

        return $party->status !== PartyStatus::Draft && $party->visibility === PartyVisibility::Public;
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

    /**
     * Determine whether the user can join the party. A public party is
     * joinable by anyone; a private one requires proof of invitation — the
     * exact room code — since knowing the party's id alone (e.g. by
     * guessing a sequential one) isn't an invitation.
     */
    public function join(User $user, Party $party, ?string $roomCode = null): bool
    {
        if ($party->host_id === $user->id) {
            return true;
        }

        if ($party->visibility === PartyVisibility::Public) {
            return true;
        }

        return $roomCode !== null && hash_equals($party->room_code, strtoupper(trim($roomCode)));
    }

    /**
     * Determine whether the user can leave the party.
     */
    public function leave(User $user, Party $party): bool
    {
        return $this->view($user, $party);
    }

    /**
     * Determine whether the user can update the party's game type/pack
     * selection. Host-only.
     */
    public function update(User $user, Party $party): bool
    {
        return $party->host_id === $user->id;
    }

    /**
     * Determine whether the user can start the party. Host-only.
     */
    public function start(User $user, Party $party): bool
    {
        return $party->host_id === $user->id;
    }

    /**
     * Determine whether the user can end the party. Host-only.
     */
    public function end(User $user, Party $party): bool
    {
        return $party->host_id === $user->id;
    }

    /**
     * Determine whether the user can cancel the party. Host-only.
     */
    public function cancel(User $user, Party $party): bool
    {
        return $party->host_id === $user->id;
    }

    /**
     * Determine whether the user can view the party's game session state.
     * The host and any current (active) member — the same people who play it.
     */
    public function viewGame(User $user, Party $party): bool
    {
        return $party->host_id === $user->id || $party->isMemberOf($user);
    }

    /**
     * Determine whether the user can start or advance a game session for the party. Host-only.
     */
    public function manageGame(User $user, Party $party): bool
    {
        return $party->host_id === $user->id;
    }
}
