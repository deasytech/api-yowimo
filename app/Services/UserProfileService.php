<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Throwable;

class UserProfileService
{
    public function __construct(private readonly AvatarUploadService $avatars) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data, ?UploadedFile $avatar = null): User
    {
        unset($data['avatar']);
        $previousAvatarPath = $user->avatar_path;
        $newAvatarPath = null;

        if ($avatar) {
            $newAvatarPath = $this->avatars->store($avatar);
            $data['avatar_url'] = $newAvatarPath;
            $user->avatar_path = $newAvatarPath;
        }

        try {
            $user->fill($data)->save();
        } catch (Throwable $exception) {
            // The upload already committed to disk before save() ran; if the
            // profile update itself never persists, the file would otherwise
            // be orphaned forever (mirrors PartyService::create()'s cover
            // image cleanup-on-failure).
            if ($newAvatarPath !== null) {
                $this->avatars->delete($newAvatarPath);
            }

            throw $exception;
        }

        if ($newAvatarPath !== null && $previousAvatarPath) {
            $this->avatars->delete($previousAvatarPath);
        }

        return $user;
    }
}
