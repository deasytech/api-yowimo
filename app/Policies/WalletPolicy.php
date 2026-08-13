<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Wallet;

class WalletPolicy
{
    /**
     * Determine whether the user can view the wallet. A user may only view their own wallet.
     */
    public function view(User $user, Wallet $wallet): bool
    {
        return $user->id === $wallet->user_id;
    }
}
