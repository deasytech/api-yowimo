<?php

use App\Models\PushToken;
use App\Models\User;
use App\Services\Notifications\PushTokenService;

it('registers a push token for a user', function () {
    $user = User::factory()->create();

    $pushToken = app(PushTokenService::class)->register($user, 'device-token', 'ios');

    expect($pushToken->user_id)->toBe($user->id);
    expect($pushToken->token)->toBe('device-token');
});

it('replaces the token when the same user registers again', function () {
    $user = User::factory()->create();
    $service = app(PushTokenService::class);

    $service->register($user, 'device-token-old', 'android');
    $service->register($user, 'device-token-new', 'ios');

    expect(PushToken::where('user_id', $user->id)->count())->toBe(1);
    expect(PushToken::where('user_id', $user->id)->first()->token)->toBe('device-token-new');
});

it('reassigns a token to the new user when a shared device re-registers under a different account', function () {
    $previousUser = User::factory()->create();
    $newUser = User::factory()->create();
    $service = app(PushTokenService::class);

    $service->register($previousUser, 'shared-device-token', 'ios');
    $service->register($newUser, 'shared-device-token', 'ios');

    expect(PushToken::where('token', 'shared-device-token')->count())->toBe(1);
    expect(PushToken::where('token', 'shared-device-token')->first()->user_id)->toBe($newUser->id);
    expect(PushToken::where('user_id', $previousUser->id)->exists())->toBeFalse();
});

it('unregisters a user push token', function () {
    $user = User::factory()->create();
    $service = app(PushTokenService::class);

    $service->register($user, 'device-token', 'ios');
    $service->unregister($user);

    expect(PushToken::where('user_id', $user->id)->exists())->toBeFalse();
});
