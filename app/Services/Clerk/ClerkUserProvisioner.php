<?php

namespace App\Services\Clerk;

use App\Enums\UserStatus;
use App\Exceptions\Api\InvalidClerkTokenException;
use App\Models\User;
use Illuminate\Support\Arr;

class ClerkUserProvisioner
{
    /**
     * Resolve the internal user for the given verified Clerk claims,
     * provisioning a new record just-in-time on first sight of a clerk_user_id.
     *
     * @param  array<string, mixed>  $claims
     *
     * @throws InvalidClerkTokenException
     */
    public function resolve(array $claims): User
    {
        $clerkUserId = $claims['sub'];

        $attributes = array_filter([
            'email' => Arr::get($claims, 'email'),
            'first_name' => Arr::get($claims, 'given_name') ?? Arr::get($claims, 'first_name'),
            'last_name' => Arr::get($claims, 'family_name') ?? Arr::get($claims, 'last_name'),
            'display_name' => Arr::get($claims, 'name'),
            'avatar_url' => Arr::get($claims, 'image_url') ?? Arr::get($claims, 'picture'),
        ], fn ($value) => $value !== null);

        $user = User::withTrashed()->where('clerk_user_id', $clerkUserId)->first();

        if (! $user) {
            return $this->touchLastSeen(User::create([
                'clerk_user_id' => $clerkUserId,
                'status' => UserStatus::Active,
                ...$attributes,
            ]));
        }

        if ($user->trashed() || $user->status === UserStatus::Deactivated) {
            throw new InvalidClerkTokenException('This account is no longer active.');
        }

        if ($attributes !== [] && $this->hasChanges($user, $attributes)) {
            $user->fill($attributes)->save();
        }

        return $this->touchLastSeen($user);
    }

    protected function touchLastSeen(User $user): User
    {
        if (is_null($user->last_seen_at) || $user->last_seen_at->lt(now()->subMinutes(5))) {
            $user->forceFill(['last_seen_at' => now()])->save();
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function hasChanges(User $user, array $attributes): bool
    {
        foreach ($attributes as $key => $value) {
            if ($user->{$key} !== $value) {
                return true;
            }
        }

        return false;
    }
}
