<?php

use App\Models\GameSession;
use App\Models\User;
use App\Services\Game\GameSessionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * A game whose last turn has been played sits in the final voting window
 * until it closes. Jump past that window and close it, completing the game
 * (which fires GameCompleted) as the delayed FinishGameVoting job would.
 */
function finishGameVotingWindow(GameSession|int $session): ?GameSession
{
    test()->travel(GameSessionService::VOTING_WINDOW_SECONDS + 1)->seconds();

    return app(GameSessionService::class)
        ->finishVoting($session instanceof GameSession ? $session->id : $session);
}

/**
 * Provisions (via a real request, matching this app's auto-provision-on-first-
 * request behavior) and returns the authenticated user for the rest of the
 * test — the Authorization header set here persists on $this for every later
 * request in the same test, so callers don't need to repeat it.
 */
function authAs(string $clerkSub): User
{
    $token = test()->clerkToken(['sub' => $clerkSub]);
    test()->withHeader('Authorization', "Bearer {$token}")->getJson('/api/v1/users/me')->assertOk();

    return User::where('clerk_user_id', $clerkSub)->firstOrFail();
}

/**
 * A QueryException shaped like a real unique-constraint violation on the
 * given `users` column — used by ClerkUserProvisionerTest/
 * ClerkUserSynchronizerTest to simulate a generated username/referral_code
 * colliding with one generated concurrently for a different user.
 */
function uniqueConstraintViolation(string $column): QueryException
{
    return new QueryException(
        'sqlite',
        'insert into "users" ...',
        [],
        new RuntimeException("SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: users.{$column}", 23000)
    );
}
