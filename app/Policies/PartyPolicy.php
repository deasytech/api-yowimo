<?php

namespace App\Policies;

use App\Enums\PartyMode;
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

        return ! in_array($party->status, [PartyStatus::Draft, PartyStatus::PendingSponsorship], true)
            && $party->visibility === PartyVisibility::Public;
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
     * Determine whether the user can add a guest (pass-and-play) player to
     * the party. Host-only — they're physically present and vouching for
     * who's in the room, so guests don't need their own invitation proof.
     */
    public function addGuest(User $user, Party $party): bool
    {
        return $party->host_id === $user->id;
    }

    /**
     * Determine whether the user can toggle their ready state. Any current
     * (active) member, including the host.
     */
    public function ready(User $user, Party $party): bool
    {
        return $party->isMemberOf($user);
    }

    /**
     * Determine whether the user can toggle their ready state. Any current
     * (active) member, including the host.
     */
    public function unready(User $user, Party $party): bool
    {
        return $party->isMemberOf($user);
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
     * Determine whether the user can create a sponsorship invite link for
     * the party. Host-only — only the host decides whether their party is
     * sponsored and for which scope.
     */
    public function createSponsorshipInvite(User $user, Party $party): bool
    {
        return $party->host_id === $user->id;
    }

    /**
     * Determine whether the user can request a video token. Any current
     * (active) member, but only once the party is live and only for an
     * online/hybrid party — an in-person party has no video component.
     */
    public function joinVideo(User $user, Party $party): bool
    {
        return $party->isMemberOf($user)
            && $party->status === PartyStatus::Live
            && in_array($party->mode, [PartyMode::Online, PartyMode::Hybrid], true);
    }

    /**
     * Determine whether the user can request a TV pairing code. Any current
     * (active) member, once the party is live — unlike joinVideo, not
     * restricted to online/hybrid: casting to a physical TV is exactly the
     * in-person/hybrid case, not something an online-only party needs less of.
     */
    public function pairTv(User $user, Party $party): bool
    {
        return $party->isMemberOf($user) && $party->status === PartyStatus::Live;
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
