<?php

namespace App\Services\Clerk;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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

        // Normalized so an empty string from Clerk (as opposed to an absent
        // key or an explicit null) is never mistaken for a real username —
        // otherwise it would survive the null-only filter below and, for a
        // user who already has a stored username, overwrite it with "".
        $clerkUsername = Arr::get($data, 'username') ?: null;
        $email = $this->primaryEmail($data);

        $attributes = array_filter([
            'email' => $email,
            'username' => $clerkUsername,
            'first_name' => Arr::get($data, 'first_name'),
            'last_name' => Arr::get($data, 'last_name'),
            'avatar_url' => Arr::get($data, 'image_url'),
        ], fn ($value) => $value !== null);

        $usedFallbackUsername = false;

        // Clerk never collects a username for OAuth-only sign-ins (Google,
        // Apple), so neither a brand new user nor one already synced
        // without one would otherwise ever get a non-null value here.
        if (! $clerkUsername && $this->missingUsername($clerkUserId)) {
            $displayName = trim(Arr::get($data, 'first_name', '').' '.Arr::get($data, 'last_name', '')) ?: null;
            $attributes['username'] = $this->usernames->generateFor($displayName, $email);
            $usedFallbackUsername = true;
        }

        try {
            return $this->upsert($clerkUserId, $attributes);
        } catch (QueryException $exception) {
            if (! $usedFallbackUsername || ! $this->isUsernameUniqueConstraintViolation($exception)) {
                throw $exception;
            }

            // Same race as ClerkUserProvisioner::createUser(): the generated
            // candidate collided with one generated concurrently for a
            // different user. generateFor() mints a fresh one each call, so
            // a single retry is enough; let a second failure propagate.
            $attributes['username'] = $this->usernames->generateFor(null, $email);

            return $this->upsert($clerkUserId, $attributes);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function upsert(string $clerkUserId, array $attributes): User
    {
        return User::query()->updateOrCreate(['clerk_user_id' => $clerkUserId], $attributes);
    }

    protected function missingUsername(string $clerkUserId): bool
    {
        return User::withTrashed()->where('clerk_user_id', $clerkUserId)->value('username') === null;
    }

    protected function isUsernameUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $isUsernameConstraint = Str::contains($message, ['username', 'users_username_unique']);
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $isUsernameConstraint && $isUniqueViolation;
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
