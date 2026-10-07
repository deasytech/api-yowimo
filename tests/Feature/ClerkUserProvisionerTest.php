<?php

use App\Models\User;
use App\Services\Clerk\ClerkUserProvisioner;
use App\Services\Clerk\FallbackUsernameGenerator;
use App\Services\Referrals\ReferralCodeGenerator;
use Illuminate\Database\QueryException;

it('assigns a unique, non-null fallback username to a newly provisioned user', function () {
    $provisioner = app(ClerkUserProvisioner::class);

    $user = $provisioner->resolve(['sub' => 'user_oauth_only', 'email' => 'oauth.only@yowimo.app']);

    expect($user->username)->not->toBeNull();
    expect($user->username)->toMatch('/^[a-zA-Z0-9_.]+$/');
});

it('gives two OAuth-only users distinct fallback usernames even with the same display name', function () {
    $provisioner = app(ClerkUserProvisioner::class);

    $first = $provisioner->resolve(['sub' => 'user_oauth_a', 'name' => 'Jordan Lee']);
    $second = $provisioner->resolve(['sub' => 'user_oauth_b', 'name' => 'Jordan Lee']);

    expect($first->username)->not->toBe($second->username);
});

it('assigns a unique referral code to a newly provisioned user', function () {
    $provisioner = app(ClerkUserProvisioner::class);

    $first = $provisioner->resolve(['sub' => 'user_referral_code_a']);
    $second = $provisioner->resolve(['sub' => 'user_referral_code_b']);

    expect($first->referral_code)->not->toBeNull();
    expect($second->referral_code)->not->toBeNull();
    expect($first->referral_code)->not->toBe($second->referral_code);
});

it('recovers from a concurrent unique clerk user id race and continues sync flow', function () {
    $existingUser = User::factory()->create([
        'clerk_user_id' => 'user_race',
        'email' => 'before@yowimo.app',
        'display_name' => 'Before Name',
        'last_seen_at' => now()->subMinutes(10),
    ]);

    $provisioner = new class(new FallbackUsernameGenerator, new ReferralCodeGenerator) extends ClerkUserProvisioner
    {
        private bool $throwOnce = true;

        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function createUser(string $clerkUserId, array $attributes): User
        {
            if ($this->throwOnce) {
                $this->throwOnce = false;

                throw uniqueConstraintViolation('clerk_user_id');
            }

            return parent::createUser($clerkUserId, $attributes);
        }
    };

    $resolvedUser = $provisioner->resolve([
        'sub' => 'user_race',
        'email' => 'after@yowimo.app',
        'name' => 'After Name',
    ]);

    expect($resolvedUser->is($existingUser))->toBeTrue();

    $existingUser->refresh();

    expect($existingUser->email)->toBe('after@yowimo.app');
    expect($existingUser->display_name)->toBe('Before Name');
    expect($existingUser->last_seen_at)->not->toBeNull();
    expect($existingUser->last_seen_at->greaterThan(now()->subMinutes(1)))->toBeTrue();
});

it('never reverts an existing user’s own first/last/display name edit back to the JWT’s claims', function () {
    $user = User::factory()->create([
        'clerk_user_id' => 'user_edited_profile',
        'first_name' => 'Edited First',
        'last_name' => 'Edited Last',
        'display_name' => 'Edited Display Name',
    ]);

    app(ClerkUserProvisioner::class)->resolve([
        'sub' => 'user_edited_profile',
        'given_name' => 'Clerk First',
        'family_name' => 'Clerk Last',
        'name' => 'Clerk Display Name',
    ]);

    $user->refresh();

    expect($user->first_name)->toBe('Edited First');
    expect($user->last_name)->toBe('Edited Last');
    expect($user->display_name)->toBe('Edited Display Name');
});

it('still keeps email and avatar_url live-synced from Clerk for an existing user', function () {
    $user = User::factory()->create([
        'clerk_user_id' => 'user_live_sync',
        'email' => 'old@yowimo.app',
        'avatar_url' => 'https://old.example/avatar.png',
    ]);

    app(ClerkUserProvisioner::class)->resolve([
        'sub' => 'user_live_sync',
        'email' => 'new@yowimo.app',
        'picture' => 'https://new.example/avatar.png',
    ]);

    $user->refresh();

    expect($user->email)->toBe('new@yowimo.app');
    expect($user->avatar_url)->toBe('https://new.example/avatar.png');
});

it('rethrows query exceptions that are unrelated to unique clerk_user_id violations', function () {
    $provisioner = new class(new FallbackUsernameGenerator, new ReferralCodeGenerator) extends ClerkUserProvisioner
    {
        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function createUser(string $clerkUserId, array $attributes): User
        {
            throw new QueryException(
                'sqlite',
                'insert into "users" ...',
                [],
                new RuntimeException('SQLSTATE[40001]: Serialization failure: deadlock detected', 40001)
            );
        }
    };

    $provisioner->resolve(['sub' => 'user_unrelated_failure']);
})->throws(QueryException::class);

it('rethrows a unique clerk_user_id violation when no user can be recovered', function () {
    $provisioner = new class(new FallbackUsernameGenerator, new ReferralCodeGenerator) extends ClerkUserProvisioner
    {
        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function createUser(string $clerkUserId, array $attributes): User
        {
            throw uniqueConstraintViolation('clerk_user_id');
        }

        protected function findUserByClerkUserId(string $clerkUserId): ?User
        {
            return null;
        }
    };

    $provisioner->resolve(['sub' => 'user_missing_after_race']);
})->throws(QueryException::class);

it('retries once with a freshly generated value when the first fallback collides', function (string $column) {
    $provisioner = new class(new FallbackUsernameGenerator, new ReferralCodeGenerator) extends ClerkUserProvisioner
    {
        public string $collidingColumn = '';

        private bool $throwOnce = true;

        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function createUser(string $clerkUserId, array $attributes): User
        {
            if ($this->throwOnce) {
                $this->throwOnce = false;

                throw uniqueConstraintViolation($this->collidingColumn);
            }

            return parent::createUser($clerkUserId, $attributes);
        }
    };
    $provisioner->collidingColumn = $column;

    $user = $provisioner->resolve(['sub' => "user_{$column}_race"]);

    expect($user)->not->toBeNull();
    expect($user->{$column})->not->toBeNull();
})->with(['username', 'referral_code']);

it('rethrows a second unique violation rather than retrying forever', function (string $column) {
    $provisioner = new class(new FallbackUsernameGenerator, new ReferralCodeGenerator) extends ClerkUserProvisioner
    {
        public string $collidingColumn = '';

        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function createUser(string $clerkUserId, array $attributes): User
        {
            throw uniqueConstraintViolation($this->collidingColumn);
        }
    };
    $provisioner->collidingColumn = $column;

    $provisioner->resolve(['sub' => "user_{$column}_race_persistent"]);
})->with(['username', 'referral_code'])->throws(QueryException::class);
