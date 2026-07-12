<?php

namespace App\Policies;

use App\Models\GameType;
use App\Models\User;

class GameTypePolicy
{
    /**
     * Determine whether the user can view any game types. Game types are public reference data.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the game type.
     */
    public function view(?User $user, GameType $gameType): bool
    {
        return $gameType->is_active;
    }
}
