<?php

namespace App\Services\Clerk;

use App\Models\User;
use Illuminate\Support\Arr;

class ClerkUserProvisioner
{
    /**
     * Resolve the internal user for the given verified Clerk claims,
     * provisioning a new record just-in-time on first sight of a clerk_id.
     *
     * @param  array<string, mixed>  $claims
     */
    public function resolve(array $claims): User
    {
        $clerkId = $claims['sub'];

        $attributes = array_filter([
            'email' => Arr::get($claims, 'email'),
            'name' => $this->resolveName($claims),
            'avatar_url' => Arr::get($claims, 'image_url') ?? Arr::get($claims, 'picture'),
        ], fn ($value) => $value !== null);

        $user = User::where('clerk_id', $clerkId)->first();

        if (! $user) {
            return User::create(['clerk_id' => $clerkId, ...$attributes]);
        }

        if ($attributes !== [] && $this->hasChanges($user, $attributes)) {
            $user->fill($attributes)->save();
        }

        return $user;
    }

    /**
     * @param  array<string, mixed>  $claims
     */
    protected function resolveName(array $claims): ?string
    {
        if ($name = Arr::get($claims, 'name')) {
            return $name;
        }

        $full = trim(sprintf('%s %s', Arr::get($claims, 'given_name', ''), Arr::get($claims, 'family_name', '')));

        return $full !== '' ? $full : null;
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
