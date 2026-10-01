<?php

use App\Models\GameType;
use App\Models\Pack;
use App\Models\Party;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
    // A CDN that happens to use "storage" as a path segment too — the naive
    // "contains /storage/" match alone would have mangled this; it's only
    // left alone because what follows isn't this app's own upload prefix.
    $lookalikeCdn = Party::factory()->create(['cover_image_url' => 'https://mycdn.example.com/storage/assets/photo-456.jpg']);

    $this->artisan('images:backfill-relative-paths')->assertExitCode(0);

    expect($external->fresh()->cover_image_url)->toBe('https://images.unsplash.com/photo-123.jpg');
    expect($empty->fresh()->cover_image_url)->toBeNull();
    expect($clerkAvatar->fresh()->avatar_url)->toBe('https://img.clerk.com/some-avatar.png');
    expect($lookalikeCdn->fresh()->cover_image_url)->toBe('https://mycdn.example.com/storage/assets/photo-456.jpg');
});

it('converts every matching row even past a single chunk boundary', function () {
    // --chunk lets this exercise the real bug each() had here without 1,000+
    // rows: a successful update removes that row from the WHERE match, so
    // offset-based paging (each()) would skip whatever the next page's
    // offset shifted past as the matched set shrank underneath it —
    // reproducible with as few as chunk-size-plus-one rows, not just at the
    // real default of 1,000.
    $now = now();

    collect(range(1, 7))->each(fn (int $i) => DB::table('users')->insert([
        'clerk_user_id' => "chunk_boundary_user_{$i}",
        'avatar_url' => 'http://old-tunnel.test/storage/avatars/photo.jpg',
        'created_at' => $now,
        'updated_at' => $now,
    ]));

    $this->artisan('images:backfill-relative-paths', ['--chunk' => 3])->assertExitCode(0);

    expect(DB::table('users')->where('avatar_url', 'avatars/photo.jpg')->count())->toBe(7);
    expect(DB::table('users')->where('avatar_url', 'like', '%old-tunnel%')->count())->toBe(0);
});

it('is safe to run again once everything is already backfilled', function () {
    $party = Party::factory()->create(['cover_image_url' => 'parties/covers/already-relative.jpg']);

    $this->artisan('images:backfill-relative-paths')->assertExitCode(0);

    expect($party->fresh()->cover_image_url)->toBe('parties/covers/already-relative.jpg');
});
