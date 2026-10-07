<?php

namespace App\Services\Clerk;

use App\Models\User;
use Illuminate\Support\Str;

/**
 * Generates a unique, non-null fallback username for Clerk-provisioned users
 * who have none — chiefly OAuth sign-ins (Google/Apple), where Clerk never
 * collects a username. Not meant to be pretty, just unique and present, so
 * nothing downstream needs a null-guard on `username`.
 */
class FallbackUsernameGenerator
{
    public function generateFor(?string $displayName, ?string $email): string
    {
        $base = Str::substr($this->slug($displayName, $email), 0, 24);

        do {
            $candidate = $base.'_'.Str::lower(Str::random(7));
        } while (User::withTrashed()->where('username', $candidate)->exists());

        return $candidate;
    }

    private function slug(?string $displayName, ?string $email): string
    {
        $source = $displayName ?: Str::before($email ?? '', '@');

        $slug = Str::of($source)
            ->lower()
            ->replaceMatches('/[^a-z0-9_.]+/', '_')
            ->trim('_.');

        return $slug->isNotEmpty() ? $slug->value() : 'user';
    }
}
