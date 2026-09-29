<?php

use App\Models\Pack;
use App\Models\PackCard;
use Database\Seeders\GameTypeSeeder;
use Database\Seeders\PackSeeder;

beforeEach(function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();
});

it('seeds the curated catalog in its marketplace display order', function () {
    $curatedSlugs = [
        'midnight-spice', 'office-icebreakers', 'sweet-silly-couples',
        'family-game-night', 'party-starter-pack', 'most-likely-to-starter',
        'would-you-rather-starter', 'two-truths-starter', 'hot-seat-starter',
        'guess-the-song-starter', 'guess-the-movie-starter', 'neon-confessions',
    ];

    $curatedPacks = Pack::query()
        ->whereIn('slug', $curatedSlugs)
        ->orderBy('sort_order')
        ->get();

    expect($curatedPacks->pluck('slug')->all())->toBe($curatedSlugs);
});

it('does not carry over a stale pack slug from an earlier naming pass', function () {
    expect(Pack::query()->where('slug', 'two-truths-classic-100')->exists())->toBeFalse();
});

it('seeds each curated pack with its expected truth/dare counts and sequential card positions', function (string $slug, string $name, int $truths, int $dares) {
    $pack = Pack::query()->where('slug', $slug)->firstOrFail();

    expect($pack->name)->toBe($name);
    expect($pack->truths_count)->toBe($truths);
    expect($pack->dares_count)->toBe($dares);
    expect($pack->cards_count)->toBe($truths + $dares);
    expect($pack->cards()->count())->toBe($truths + $dares);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, $truths + $dares - 1));
})->with([
    'two truths starter' => ['two-truths-starter', 'Two Truths Starter', 52, 51],
    'family game night' => ['family-game-night', 'Family Game Night', 52, 52],
    'would you rather starter' => ['would-you-rather-starter', 'Would You Rather Starter', 77, 27],
    'sweet and silly couples' => ['sweet-silly-couples', 'Sweet & Silly Couples', 64, 40],
    // Deliberately dare-heavy (45/55 curated split), unlike the truth-heavy or balanced packs.
    'party starter pack' => ['party-starter-pack', 'Party Starter Pack', 47, 57],
    'office icebreakers' => ['office-icebreakers', 'Office Icebreakers', 57, 47],
    // 120 curated cards (72 truth/48 dare) plus the 4 preview cards exceed
    // the pack's 70/50 target in both kinds, so seedCuratedCards() needs no
    // random filler — truths_count ends up above the original 70 target
    // since curated content isn't capped, only topped up when it falls short.
    'neon confessions' => ['neon-confessions', 'Neon Confessions', 74, 50],
    'most likely to starter' => ['most-likely-to-starter', 'Most Likely To Starter', 62, 42],
    'midnight spice' => ['midnight-spice', 'Midnight Spice', 52, 52],
    // Deliberately truth-heavy (70/30 curated split), unlike the other curated packs' 50/50 split.
    'hot seat starter' => ['hot-seat-starter', 'Hot Seat Starter', 72, 32],
    'guess the song starter' => ['guess-the-song-starter', 'Guess the Song Starter', 52, 52],
    // The curated deck's first 2 truth / 2 dare entries intentionally match
    // the pack's 4 preview cards verbatim (matching their tone was the
    // brief) — seedCuratedCards() dedupes those against the preview set
    // rather than adding them twice, so 100 curated cards net 96 new + 4
    // preview = 100.
    'guess the movie starter' => ['guess-the-movie-starter', 'Guess the Movie Starter', 50, 50],
]);

it('syncs the randomized marketplace packs count metadata to their actual attached cards', function () {
    $curatedSlugs = [
        'midnight-spice', 'office-icebreakers', 'sweet-silly-couples',
        'family-game-night', 'party-starter-pack', 'neon-confessions',
        // Free starter decks for the game types that had no pack of their own.
        'most-likely-to-starter', 'would-you-rather-starter', 'two-truths-starter',
        'hot-seat-starter', 'guess-the-song-starter', 'guess-the-movie-starter',
    ];

    $marketplacePacks = Pack::query()->whereNotIn('slug', $curatedSlugs)->get();

    expect($marketplacePacks)->toHaveCount(6);

    foreach ($marketplacePacks as $pack) {
        $actualTruths = $pack->cards()->where('kind', 'truth')->count();
        $actualDares = $pack->cards()->where('kind', 'dare')->count();

        expect($pack->truths_count)->toBe($actualTruths);
        expect($pack->dares_count)->toBe($actualDares);
        expect($pack->cards_count)->toBe($actualTruths + $actualDares);
        expect($pack->cards()->count())->toBe($pack->cards_count);
    }
});
