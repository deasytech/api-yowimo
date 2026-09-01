<?php

namespace App\Policies;

use App\Models\PartyMember;
use App\Models\Turn;
use App\Models\User;

class TurnPolicy
{
    /**
     * Determine whether the user can vote on the turn. Any party member other
     * than the turn's own player may vote.
     */
    public function vote(User $user, Turn $turn): bool
    {
        if ($user->id === $turn->user_id) {
            return false;
        }

        return PartyMember::query()
            ->where('party_id', $turn->gameSession->party_id)
            ->where('user_id', $user->id)
            ->exists();
    }
}
