<?php

use App\Support\StoredImageUrl;
use Illuminate\Support\Facades\Storage;

it('returns null as-is', function () {
    expect(StoredImageUrl::resolve(null))->toBeNull();
});

it('returns an already-absolute URL unchanged, never re-resolved', function () {
    $url = 'https://images.unsplash.com/photo-123.jpg';

    expect(StoredImageUrl::resolve($url))->toBe($url);
});

it('resolves a disk-relative path to a full public URL using the current disk config', function () {
    config(['filesystems.disks.public.url' => 'https://api.yowimo.com/storage']);

    expect(StoredImageUrl::resolve('parties/covers/abc.jpg'))
        ->toBe('https://api.yowimo.com/storage/parties/covers/abc.jpg');
});

it('resolves the same stored path differently as the disk config changes, instead of baking in one host', function () {
    config(['filesystems.disks.public.url' => 'https://tunnel-one.test/storage']);
    $first = StoredImageUrl::resolve('parties/covers/abc.jpg');

    // Storage::disk() caches the resolved disk instance, so changing config
    // alone wouldn't be picked up here — this simulates what actually
    // changes between requests in the real bug: a fresh process picking up
    // a new APP_URL, not the same disk instance mutating mid-request.
    config(['filesystems.disks.public.url' => 'https://tunnel-two.test/storage']);
    Storage::forgetDisk('public');
    $second = StoredImageUrl::resolve('parties/covers/abc.jpg');

    expect($first)->toBe('https://tunnel-one.test/storage/parties/covers/abc.jpg');
    expect($second)->toBe('https://tunnel-two.test/storage/parties/covers/abc.jpg');
});
