<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

function clerkApiUser(string $id, string $username): array
{
    return [
        'id' => $id,
        'username' => $username,
        'first_name' => 'First',
        'last_name' => 'Last',
        'image_url' => 'https://img.clerk.com/avatar.png',
        'primary_email_address_id' => 'idn_1',
        'email_addresses' => [
            ['id' => 'idn_1', 'email_address' => "{$username}@yowimo.app"],
        ],
    ];
}

it('fails fast when no secret key is configured', function () {
    config(['services.clerk.secret_key' => null]);

    $this->artisan('clerk:sync-users')->assertExitCode(1);
});

it('backfills every user returned by the Clerk Backend API', function () {
    config(['services.clerk.secret_key' => 'sk_test_123']);

    Http::fake([
        'api.clerk.com/v1/users*' => Http::response([
            'data' => [
                clerkApiUser('user_one', 'userone'),
                clerkApiUser('user_two', 'usertwo'),
            ],
            'total_count' => 2,
        ]),
    ]);

    $this->artisan('clerk:sync-users')
        ->expectsOutputToContain('Synced 2 user(s) from Clerk.')
        ->assertExitCode(0);

    expect(User::count())->toBe(2);
    expect(User::where('clerk_user_id', 'user_one')->first()->email)->toBe('userone@yowimo.app');
    expect(User::where('clerk_user_id', 'user_two')->first()->username)->toBe('usertwo');

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer sk_test_123'));
});

it('paginates through multiple pages of users', function () {
    config(['services.clerk.secret_key' => 'sk_test_123']);

    $firstPage = array_map(fn ($i) => clerkApiUser("user_{$i}", "user{$i}"), range(1, 500));
    $secondPage = [clerkApiUser('user_501', 'user501')];

    Http::fakeSequence()
        ->push(['data' => $firstPage])
        ->push(['data' => $secondPage]);

    $this->artisan('clerk:sync-users')->assertExitCode(0);

    expect(User::count())->toBe(501);
});

it('handles a raw array response body, not just the wrapped shape', function () {
    config(['services.clerk.secret_key' => 'sk_test_123']);

    Http::fake([
        'api.clerk.com/v1/users*' => Http::response([
            clerkApiUser('user_raw', 'userraw'),
        ]),
    ]);

    $this->artisan('clerk:sync-users')->assertExitCode(0);

    expect(User::where('clerk_user_id', 'user_raw')->exists())->toBeTrue();
});

it('fails when the Clerk API returns an error', function () {
    config(['services.clerk.secret_key' => 'sk_test_bad']);

    Http::fake([
        'api.clerk.com/v1/users*' => Http::response(['errors' => ['Unauthorized']], 401),
    ]);

    $this->artisan('clerk:sync-users')->assertExitCode(1);

    expect(User::count())->toBe(0);
});
