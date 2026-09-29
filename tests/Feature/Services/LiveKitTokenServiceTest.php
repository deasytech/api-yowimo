<?php

use App\Enums\PartyMode;
use App\Exceptions\Api\LiveKitNotConfiguredException;
use App\Models\Party;
use App\Models\User;
use App\Services\Video\LiveKitTokenService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

beforeEach(function () {
    config(['services.livekit.api_key' => 'lk_test_key', 'services.livekit.api_secret' => 'lk_test_secret_at_least_32_characters_long']);
});

it('throws when LiveKit is not configured', function () {
    config(['services.livekit.api_key' => null, 'services.livekit.api_secret' => null]);
    $party = Party::factory()->create(['mode' => PartyMode::Online]);
    $user = User::factory()->create();

    app(LiveKitTokenService::class)->tokenFor($user, $party);
})->throws(LiveKitNotConfiguredException::class);

it('mints a token scoped to the party room with publish and subscribe granted', function () {
    $party = Party::factory()->create(['mode' => PartyMode::Online]);
    $user = User::factory()->create(['display_name' => 'Maya C.']);

    $token = app(LiveKitTokenService::class)->tokenFor($user, $party);

    $claims = (array) JWT::decode($token, new Key('lk_test_secret_at_least_32_characters_long', 'HS256'));

    expect($claims['iss'])->toBe('lk_test_key');
    expect($claims['sub'])->toBe((string) $user->id);
    expect($claims['name'])->toBe('Maya C.');
    expect($claims['exp'])->toBeGreaterThan(time());
    expect($claims['nbf'])->toBeLessThanOrEqual(time());

    $video = (array) $claims['video'];
    expect($video['room'])->toBe("party-{$party->id}");
    expect($video['roomJoin'])->toBeTrue();
    expect($video['canPublish'])->toBeTrue();
    expect($video['canSubscribe'])->toBeTrue();
});

it('falls back to username, then a generic label, when display_name is missing', function () {
    $party = Party::factory()->create(['mode' => PartyMode::Online]);
    $user = User::factory()->create(['display_name' => null, 'username' => 'sam']);

    $token = app(LiveKitTokenService::class)->tokenFor($user, $party);
    $claims = (array) JWT::decode($token, new Key('lk_test_secret_at_least_32_characters_long', 'HS256'));

    expect($claims['name'])->toBe('sam');
});
