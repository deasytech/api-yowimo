<?php

namespace App\Services\Clerk;

use App\Models\User;
use App\Services\Referrals\ReferralCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ClerkUserSynchronizer
{
    public function __construct(
        private readonly FallbackUsernameGenerator $usernames,
        private readonly ReferralCodeGenerator $referralCodes,
    ) {}

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

        // Clerk has no concept of a referral code at all, so a brand new
        // user (or an existing one somehow still missing one) always needs
        // one generated here — always our own fallback, never Clerk-supplied.
        $usedFallbackReferralCode = $this->missingReferralCode($clerkUserId);

        if ($usedFallbackReferralCode) {
            $attributes['referral_code'] = $this->referralCodes->generate();
        }

        try {
            return $this->upsert($clerkUserId, $attributes);
        } catch (QueryException $exception) {
            // Same race as ClerkUserProvisioner::createUser(): a generated
            // fallback collided with one generated concurrently for a
            // different user. Both generators mint a fresh candidate each
            // call, so a single retry is enough; let a second failure
            // (or any unrelated exception, or a collision on a real
            // Clerk-supplied username) propagate instead of silently
            // replacing a value we didn't generate ourselves.
            if ($usedFallbackUsername && $this->isUsernameUniqueConstraintViolation($exception)) {
                $attributes['username'] = $this->usernames->generateFor(null, $email);
            } elseif ($usedFallbackReferralCode && $this->isReferralCodeUniqueConstraintViolation($exception)) {
                $attributes['referral_code'] = $this->referralCodes->generate();
            } else {
                throw $exception;
            }

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

    protected function missingReferralCode(string $clerkUserId): bool
    {
        return User::withTrashed()->where('clerk_user_id', $clerkUserId)->value('referral_code') === null;
    }

    protected function isUsernameUniqueConstraintViolation(QueryException $exception): bool
    {
        return $this->isUniqueConstraintViolation($exception, ['username', 'users_username_unique']);
    }

    protected function isReferralCodeUniqueConstraintViolation(QueryException $exception): bool
    {
        return $this->isUniqueConstraintViolation($exception, ['referral_code', 'users_referral_code_unique']);
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function isUniqueConstraintViolation(QueryException $exception, array $needles): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $matchesColumn = Str::contains($message, $needles);
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $matchesColumn && $isUniqueViolation;
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
