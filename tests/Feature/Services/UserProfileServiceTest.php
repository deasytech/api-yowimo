<?php

use App\Models\User;
use App\Services\AvatarUploadService;
use App\Services\UserProfileService;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('deletes the newly uploaded avatar file when the profile save fails', function () {
    Storage::fake('public');

    User::factory()->create(['username' => 'taken_username']);
    $user = User::factory()->create();

    // Calling the service directly bypasses UpdateProfileRequest's
    // Rule::unique pre-check, forcing a real unique-constraint QueryException
    // at save() time — the same way PartyServiceTest forces a real FK
    // violation to test PartyService's cover-image cleanup-on-failure.
    expect(fn () => app(UserProfileService::class)->updateProfile(
        $user,
        ['username' => 'taken_username'],
        UploadedFile::fake()->image('avatar.jpg')
    ))->toThrow(QueryException::class);

    expect(Storage::disk('public')->allFiles('avatars'))->toBeEmpty();
    expect($user->fresh()->avatar_url)->toBeNull();
});

it('keeps the previous avatar file when the profile save fails', function () {
    Storage::fake('public');

    User::factory()->create(['username' => 'taken_username_2']);
    $user = User::factory()->create();

    $firstStored = app(AvatarUploadService::class)->store(UploadedFile::fake()->image('first.jpg'));
    $user->forceFill(['avatar_url' => $firstStored['url'], 'avatar_path' => $firstStored['path']])->save();

    expect(fn () => app(UserProfileService::class)->updateProfile(
        $user,
        ['username' => 'taken_username_2'],
        UploadedFile::fake()->image('second.jpg')
    ))->toThrow(QueryException::class);

    Storage::disk('public')->assertExists($firstStored['path']);
    expect($user->fresh()->avatar_url)->toBe($firstStored['url']);
});
