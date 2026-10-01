<?php

use App\Models\GameType;
use App\Models\Pack;
use App\Models\Party;
use App\Models\User;

it('converts locally-resolved absolute URLs back to relative paths, across all four tables', function () {
    $party = Party::factory()->create(['cover_image_url' => 'http://old-tunnel.test/storage/parties/covers/abc.jpg']);
    $pack = Pack::factory()->create(['cover_image_url' => 'http://old-tunnel.test/storage/packs/covers/def.jpg']);
    $gameType = GameType::factory()->create(['image_url' => 'http://old-tunnel.test/storage/game-types/ghi.jpg']);
    $user = User::factory()->create(['avatar_url' => 'http://old-tunnel.test/storage/avatars/jkl.jpg']);

    $this->artisan('images:backfill-relative-paths')->assertExitCode(0);

    expect($party->fresh()->cover_image_url)->toBe('parties/covers/abc.jpg');
    expect($pack->fresh()->cover_image_url)->toBe('packs/covers/def.jpg');
    expect($gameType->fresh()->image_url)->toBe('game-types/ghi.jpg');
    expect($user->fresh()->avatar_url)->toBe('avatars/jkl.jpg');
});

it('leaves genuine external URLs and null values untouched', function () {
    $external = Party::factory()->create(['cover_image_url' => 'https://images.unsplash.com/photo-123.jpg']);
    $empty = Party::factory()->create(['cover_image_url' => null]);
    // The overwhelming majority of real avatar_url values: Clerk's own
    // hosted CDN link, synced in directly — never a local path.
    $clerkAvatar = User::factory()->create(['avatar_url' => 'https://img.clerk.com/some-avatar.png']);

    $this->artisan('images:backfill-relative-paths')->assertExitCode(0);

    expect($external->fresh()->cover_image_url)->toBe('https://images.unsplash.com/photo-123.jpg');
    expect($empty->fresh()->cover_image_url)->toBeNull();
    expect($clerkAvatar->fresh()->avatar_url)->toBe('https://img.clerk.com/some-avatar.png');
});

it('is safe to run again once everything is already backfilled', function () {
    $party = Party::factory()->create(['cover_image_url' => 'parties/covers/already-relative.jpg']);

    $this->artisan('images:backfill-relative-paths')->assertExitCode(0);

    expect($party->fresh()->cover_image_url)->toBe('parties/covers/already-relative.jpg');
});
