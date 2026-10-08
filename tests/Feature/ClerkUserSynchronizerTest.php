<?php

use App\Models\User;
use App\Services\Clerk\ClerkUserSynchronizer;
use App\Services\Clerk\FallbackUsernameGenerator;
use App\Services\Referrals\ReferralCodeGenerator;
use Illuminate\Database\QueryException;

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

it('retries once with a freshly generated value when the first fallback collides', function (string $column) {
    $synchronizer = new class(new FallbackUsernameGenerator, new ReferralCodeGenerator) extends ClerkUserSynchronizer
    {
        public string $collidingColumn = '';

        private bool $throwOnce = true;

        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function upsert(string $clerkUserId, array $attributes): User
        {
            if ($this->throwOnce) {
                $this->throwOnce = false;

                throw uniqueConstraintViolation($this->collidingColumn);
            }

            return parent::upsert($clerkUserId, $attributes);
        }
    };
    $synchronizer->collidingColumn = $column;

    $user = $synchronizer->sync(['id' => "user_sync_{$column}_race"]);

    expect($user)->not->toBeNull();
    expect($user->{$column})->not->toBeNull();
})->with(['username', 'referral_code']);

it('rethrows a second unique violation rather than retrying forever', function (string $column) {
    $synchronizer = new class(new FallbackUsernameGenerator, new ReferralCodeGenerator) extends ClerkUserSynchronizer
    {
        public string $collidingColumn = '';

        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function upsert(string $clerkUserId, array $attributes): User
        {
            throw uniqueConstraintViolation($this->collidingColumn);
        }
    };
    $synchronizer->collidingColumn = $column;

    $synchronizer->sync(['id' => "user_sync_{$column}_race_persistent"]);
})->with(['username', 'referral_code'])->throws(QueryException::class);

it('does not retry a username collision that came from a real Clerk-supplied username, not a generated fallback', function () {
    $synchronizer = new class(new FallbackUsernameGenerator, new ReferralCodeGenerator) extends ClerkUserSynchronizer
    {
        /**
         * @param  array<string, mixed>  $attributes
         */
        protected function upsert(string $clerkUserId, array $attributes): User
        {
            throw uniqueConstraintViolation('username');
        }
    };

    // A real Clerk-supplied username (not a generated fallback) colliding
    // must surface as an error rather than be silently replaced with a
    // random one — only ever retry a collision we caused ourselves.
    $synchronizer->sync(['id' => 'user_sync_unrelated_failure', 'username' => 'real-clerk-username']);
})->throws(QueryException::class);

it('generates a referral code for a brand new user — Clerk never supplies one', function () {
    app(ClerkUserSynchronizer::class)->sync(['id' => 'user_sync_referral_code_new']);

    $referralCode = User::where('clerk_user_id', 'user_sync_referral_code_new')->first()->referral_code;

    expect($referralCode)->not->toBeNull();
});

it('never regenerates an already-set referral code on a later resync', function () {
    $existing = User::factory()->create(['clerk_user_id' => 'user_sync_referral_code_resync', 'referral_code' => 'ALREADYSET']);

    app(ClerkUserSynchronizer::class)->sync(['id' => 'user_sync_referral_code_resync']);

    expect($existing->refresh()->referral_code)->toBe('ALREADYSET');
});
