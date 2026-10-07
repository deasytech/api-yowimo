<?php

use App\Models\User;
use App\Services\Clerk\ClerkUserProvisioner;
use App\Services\Clerk\FallbackUsernameGenerator;
use Illuminate\Database\QueryException;

const NEW_USER = 'insert into "users" ...';

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

it('recovers from a concurrent unique clerk user id race and continues sync flow', function () {
    $existingUser = User::factory()->create([
        'clerk_user_id' => 'user_race',
        'email' => 'before@yowimo.app',
        'display_name' => 'Before Name',
        'last_seen_at' => now()->subMinutes(10),
    ]);

    $provisioner = new class(new FallbackUsernameGenerator) extends ClerkUserProvisioner
    {
        private bool $throwOnce = true;

        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function createUser(string $clerkUserId, array $attributes): User
        {
            if ($this->throwOnce) {
                $this->throwOnce = false;

                throw new QueryException(
                    'sqlite',
                    NEW_USER,
                    [],
                    new RuntimeException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.clerk_user_id', 23000)
                );
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
    $provisioner = new class(new FallbackUsernameGenerator) extends ClerkUserProvisioner
    {
        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function createUser(string $clerkUserId, array $attributes): User
        {
            throw new QueryException(
                'sqlite',
                NEW_USER,
                [],
                new RuntimeException('SQLSTATE[40001]: Serialization failure: deadlock detected', 40001)
            );
        }
    };

    $provisioner->resolve(['sub' => 'user_unrelated_failure']);
})->throws(QueryException::class);

it('rethrows a unique clerk_user_id violation when no user can be recovered', function () {
    $provisioner = new class(new FallbackUsernameGenerator) extends ClerkUserProvisioner
    {
        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function createUser(string $clerkUserId, array $attributes): User
        {
            throw new QueryException(
                'sqlite',
                NEW_USER,
                [],
                new RuntimeException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.clerk_user_id', 23000)
            );
        }

        protected function findUserByClerkUserId(string $clerkUserId): ?User
        {
            return null;
        }
    };

    $provisioner->resolve(['sub' => 'user_missing_after_race']);
})->throws(QueryException::class);
