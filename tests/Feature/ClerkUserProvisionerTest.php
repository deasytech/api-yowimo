<?php

use App\Models\User;
use App\Services\Clerk\ClerkUserProvisioner;
use Illuminate\Database\QueryException;

it('recovers from a concurrent unique clerk user id race and continues sync flow', function () {
    $existingUser = User::factory()->create([
        'clerk_user_id' => 'user_race',
        'email' => 'before@yowimo.app',
        'display_name' => 'Before Name',
        'last_seen_at' => now()->subMinutes(10),
    ]);

    $provisioner = new class extends ClerkUserProvisioner
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
                    'insert into "users" ...',
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
    expect($existingUser->display_name)->toBe('After Name');
    expect($existingUser->last_seen_at)->not->toBeNull();
    expect($existingUser->last_seen_at->greaterThan(now()->subMinutes(1)))->toBeTrue();
});

it('rethrows query exceptions that are unrelated to unique clerk_user_id violations', function () {
    $provisioner = new class extends ClerkUserProvisioner
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
    $provisioner = new class extends ClerkUserProvisioner
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
