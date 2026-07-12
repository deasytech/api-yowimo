<?php

namespace App\Services;

use App\Models\User;

class UserProfileService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User
    {
        $user->fill($data)->save();

        return $user;
    }
}
