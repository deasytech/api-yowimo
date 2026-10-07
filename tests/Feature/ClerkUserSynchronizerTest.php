<?php

use App\Models\User;
use App\Services\Clerk\ClerkUserSynchronizer;
use App\Services\Clerk\FallbackUsernameGenerator;
use Illuminate\Database\QueryException;

const SYNC_UPSERT = 'insert into "users" ...';

it('treats an empty string username from Clerk the same as absent, preserving the stored one', function () {
    User::factory()->create(['clerk_user_id' => 'user_blank_username', 'username' => 'already_set']);

    app(ClerkUserSynchronizer::class)->sync([
        'id' => 'user_blank_username',
        'username' => '',
    ]);

    expect(User::where('clerk_user_id', 'user_blank_username')->first()->username)->toBe('already_set');
});

it('generates a fallback username when Clerk sends an empty string for a user with none', function () {
    app(ClerkUserSynchronizer::class)->sync([
        'id' => 'user_blank_username_new',
        'username' => '',
    ]);

    $username = User::where('clerk_user_id', 'user_blank_username_new')->first()->username;

    expect($username)->not->toBeNull();
    expect($username)->toMatch('/^[a-zA-Z0-9_.]+$/');
});

it('retries once with a freshly generated username when the first fallback collides', function () {
    $synchronizer = new class(new FallbackUsernameGenerator) extends ClerkUserSynchronizer
    {
        private bool $throwOnce = true;

        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function upsert(string $clerkUserId, array $attributes): User
        {
            if ($this->throwOnce) {
                $this->throwOnce = false;

                throw new QueryException(
                    'sqlite',
                    SYNC_UPSERT,
                    [],
                    new RuntimeException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.username', 23000)
                );
            }

            return parent::upsert($clerkUserId, $attributes);
        }
    };

    $user = $synchronizer->sync(['id' => 'user_sync_username_race']);

    expect($user)->not->toBeNull();
    expect($user->username)->not->toBeNull();
});

it('rethrows a second unique username violation rather than retrying forever', function () {
    $synchronizer = new class(new FallbackUsernameGenerator) extends ClerkUserSynchronizer
    {
        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function upsert(string $clerkUserId, array $attributes): User
        {
            throw new QueryException(
                'sqlite',
                SYNC_UPSERT,
                [],
                new RuntimeException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.username', 23000)
            );
        }
    };

    $synchronizer->sync(['id' => 'user_sync_username_race_persistent']);
})->throws(QueryException::class);

it('does not retry a username collision that came from a real Clerk-supplied username, not a generated fallback', function () {
    $synchronizer = new class(new FallbackUsernameGenerator) extends ClerkUserSynchronizer
    {
        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function upsert(string $clerkUserId, array $attributes): User
        {
            throw new QueryException(
                'sqlite',
                SYNC_UPSERT,
                [],
                new RuntimeException('SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.username', 23000)
            );
        }
    };

    // A real Clerk-supplied username (not a generated fallback) colliding
    // must surface as an error rather than be silently replaced with a
    // random one — only ever retry a collision we caused ourselves.
    $synchronizer->sync(['id' => 'user_sync_unrelated_failure', 'username' => 'real-clerk-username']);
})->throws(QueryException::class);
