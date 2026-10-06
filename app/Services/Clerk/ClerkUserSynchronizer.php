<?php

namespace App\Services\Clerk;

use App\Models\User;
use Illuminate\Support\Arr;

class ClerkUserSynchronizer
{
    public function __construct(private readonly FallbackUsernameGenerator $usernames) {}

    /**
     * Upsert a local user from a Clerk "User" object, as found in both
     * webhook payloads (`data`) and Backend API list/get responses.
     *
     * @param  array<string, mixed>  $data
     */
    public function sync(array $data): ?User
    {
        $clerkUserId = Arr::get($data, 'id');

        if (! $clerkUserId) {
            return null;
        }

        $clerkUsername = Arr::get($data, 'username');
        $email = $this->primaryEmail($data);

        $attributes = array_filter([
            'email' => $email,
            'username' => $clerkUsername,
            'first_name' => Arr::get($data, 'first_name'),
            'last_name' => Arr::get($data, 'last_name'),
            'avatar_url' => Arr::get($data, 'image_url'),
        ], fn ($value) => $value !== null);

        // Clerk never collects a username for OAuth-only sign-ins (Google,
        // Apple), so neither a brand new user nor one already synced
        // without one would otherwise ever get a non-null value here.
        if (! $clerkUsername && $this->missingUsername($clerkUserId)) {
            $displayName = trim(Arr::get($data, 'first_name', '').' '.Arr::get($data, 'last_name', '')) ?: null;
            $attributes['username'] = $this->usernames->generateFor($displayName, $email);
        }

        return User::query()->updateOrCreate(['clerk_user_id' => $clerkUserId], $attributes);
    }

    protected function missingUsername(string $clerkUserId): bool
    {
        return User::withTrashed()->where('clerk_user_id', $clerkUserId)->value('username') === null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function primaryEmail(array $data): ?string
    {
        $primaryId = Arr::get($data, 'primary_email_address_id');
        $addresses = Arr::get($data, 'email_addresses', []);

        foreach ($addresses as $address) {
            if (Arr::get($address, 'id') === $primaryId) {
                return Arr::get($address, 'email_address');
            }
        }

        return Arr::get($addresses, '0.email_address');
    }
}
