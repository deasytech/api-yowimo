<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;

class UserProfileService
{
    public function __construct(private readonly AvatarUploadService $avatars) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        unset($data['avatar']);
        $previousAvatarUrl = $user->avatar_url;

        if ($avatar) {
            $data['avatar_url'] = $this->avatars->store($avatar);
        }

        $user->fill($data)->save();

        if ($avatar && $previousAvatarUrl) {
            $this->avatars->delete($previousAvatarUrl);
        }

        return $user;
    }
}
