<?php

namespace App\Services\Clerk;

use App\Enums\UserStatus;
use App\Exceptions\Api\InvalidClerkTokenException;
use App\Models\User;
use App\Services\Referrals\ReferralCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class ClerkUserProvisioner
{
    public function __construct(
        private readonly FallbackUsernameGenerator $usernames,
        private readonly ReferralCodeGenerator $referralCodes,
    ) {}

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

        $user = $this->findUserByClerkUserId($clerkUserId);

        if (! $user) {
            try {
                $user = $this->createUser($clerkUserId, $attributes);
            } catch (QueryException $exception) {
                if ($this->isUsernameUniqueConstraintViolation($exception) || $this->isReferralCodeUniqueConstraintViolation($exception)) {
                    // The generated fallback username/referral code collided
                    // with one generated concurrently for a different user —
                    // both generators only check availability before the
                    // insert, not atomically with it. createUser() mints
                    // fresh ones each call, so a single retry is enough; let
                    // a second failure propagate.
                    $user = $this->createUser($clerkUserId, $attributes);
                } elseif ($this->isClerkUserIdUniqueConstraintViolation($exception)) {
                    $user = $this->findUserByClerkUserId($clerkUserId);

                    if (! $user) {
                        throw $exception;
                    }
                } else {
                    throw $exception;
                }
            }
        }

        if ($user->trashed() || $user->status === UserStatus::Deactivated) {
            throw new InvalidClerkTokenException('This account is no longer active.');
        }

        // This runs on every authenticated request, not just at creation —
        // first/last/display name are editable in-app (see
        // UpdateProfileRequest), so re-applying them from the JWT here would
        // silently revert a user's own edit on their very next request. Only
        // email/avatar_url stay live-synced; everything else is set once,
        // at createUser(), and is app-owned from then on.
        $liveSyncedAttributes = Arr::only($attributes, ['email', 'avatar_url']);

        if ($liveSyncedAttributes !== [] && $this->hasChanges($user, $liveSyncedAttributes)) {
            $user->fill($liveSyncedAttributes)->save();
        }

        return $this->touchLastSeen($user);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createUser(string $clerkUserId, array $attributes): User
    {
        return User::create([
            'clerk_user_id' => $clerkUserId,
            'status' => UserStatus::Active,
            'username' => $this->usernames->generateFor($attributes['display_name'] ?? null, $attributes['email'] ?? null),
            'referral_code' => $this->referralCodes->generate(),
            ...$attributes,
        ]);
    }

    protected function findUserByClerkUserId(string $clerkUserId): ?User
    {
        return User::withTrashed()->where('clerk_user_id', $clerkUserId)->first();
    }

    protected function isClerkUserIdUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $isClerkUserIdConstraint = Str::contains($message, ['clerk_user_id', 'users_clerk_user_id_unique']);
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $isClerkUserIdConstraint && $isUniqueViolation;
    }

    protected function isUsernameUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $isUsernameConstraint = Str::contains($message, ['username', 'users_username_unique']);
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $isUsernameConstraint && $isUniqueViolation;
    }

    protected function isReferralCodeUniqueConstraintViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $isReferralCodeConstraint = Str::contains($message, ['referral_code', 'users_referral_code_unique']);
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $isReferralCodeConstraint && $isUniqueViolation;
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
