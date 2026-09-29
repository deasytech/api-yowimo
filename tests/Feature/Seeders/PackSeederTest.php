<?php

use App\Models\Pack;
use App\Models\PackCard;
use Database\Seeders\GameTypeSeeder;
use Database\Seeders\PackSeeder;

it('gives each curated pack sequential, non-colliding card positions', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    // Scoped to the curated packs (identified by slug) that this fix covers;
    // the randomized filler packs seeded afterward are a separate, unreported
    // instance of the same class of bug and are intentionally out of scope.
    $pack = Pack::query()->where('slug', 'midnight-spice')->firstOrFail();

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, $positions->count() - 1));
});

it('seeds the curated catalog in its marketplace display order', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

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

    foreach ($curatedPacks as $pack) {
        expect($pack->cards()->where('is_preview', true)->count())->toBe(4);
    }
});

it('seeds the two truths starter with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'two-truths-starter')->firstOrFail();

    expect($pack->name)->toBe('Two Truths Starter');
    expect($pack->truths_count)->toBe(52);
    expect($pack->dares_count)->toBe(51);
    expect($pack->cards_count)->toBe(103);
    expect($pack->cards()->count())->toBe(103);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 102));

    expect(Pack::query()->where('slug', 'two-truths-classic-100')->exists())->toBeFalse();
});

it('seeds family game night with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'family-game-night')->firstOrFail();

    expect($pack->name)->toBe('Family Game Night');
    expect($pack->truths_count)->toBe(52);
    expect($pack->dares_count)->toBe(52);
    expect($pack->cards_count)->toBe(104);
    expect($pack->cards()->count())->toBe(104);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 103));
});

it('seeds would you rather starter with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'would-you-rather-starter')->firstOrFail();

    expect($pack->name)->toBe('Would You Rather Starter');
    expect($pack->truths_count)->toBe(77);
    expect($pack->dares_count)->toBe(27);
    expect($pack->cards_count)->toBe(104);
    expect($pack->cards()->count())->toBe(104);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 103));
});

it('seeds sweet and silly couples with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'sweet-silly-couples')->firstOrFail();

    expect($pack->name)->toBe('Sweet & Silly Couples');
    expect($pack->truths_count)->toBe(64);
    expect($pack->dares_count)->toBe(40);
    expect($pack->cards_count)->toBe(104);
    expect($pack->cards()->count())->toBe(104);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 103));
});

it('seeds party starter pack with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'party-starter-pack')->firstOrFail();

    // Deliberately dare-heavy (45/55), unlike the truth-heavy or balanced packs.
    expect($pack->name)->toBe('Party Starter Pack');
    expect($pack->truths_count)->toBe(47);
    expect($pack->dares_count)->toBe(57);
    expect($pack->cards_count)->toBe(104);
    expect($pack->cards()->count())->toBe(104);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 103));
});

it('seeds office icebreakers with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'office-icebreakers')->firstOrFail();

    expect($pack->name)->toBe('Office Icebreakers');
    expect($pack->truths_count)->toBe(57);
    expect($pack->dares_count)->toBe(47);
    expect($pack->cards_count)->toBe(104);
    expect($pack->cards()->count())->toBe(104);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 103));
});

it('seeds neon confessions fully curated, exceeding its original truth target', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'neon-confessions')->firstOrFail();

    // 120 curated cards (72 truth/48 dare) plus the 4 preview cards give
    // 74 truth/50 dare — meeting or exceeding the pack's 70/50 target in
    // both kinds, so seedCuratedCards() needs no random filler at all.
    // truths_count ends up above the original 70 target since curated
    // content isn't capped, only topped up when it falls short.
    expect($pack->name)->toBe('Neon Confessions');
    expect($pack->truths_count)->toBe(74);
    expect($pack->dares_count)->toBe(50);
    expect($pack->cards_count)->toBe(124);
    expect($pack->cards()->count())->toBe(124);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 123));
});

it('seeds most likely to starter with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'most-likely-to-starter')->firstOrFail();

    expect($pack->name)->toBe('Most Likely To Starter');
    expect($pack->truths_count)->toBe(62);
    expect($pack->dares_count)->toBe(42);
    expect($pack->cards_count)->toBe(104);
    expect($pack->cards()->count())->toBe(104);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 103));
});

it('seeds midnight spice with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'midnight-spice')->firstOrFail();

    expect($pack->name)->toBe('Midnight Spice');
    expect($pack->truths_count)->toBe(52);
    expect($pack->dares_count)->toBe(52);
    expect($pack->cards_count)->toBe(104);
    expect($pack->cards()->count())->toBe(104);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 103));
});

it('seeds hot seat starter with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'hot-seat-starter')->firstOrFail();

    expect($pack->name)->toBe('Hot Seat Starter');
    // Deliberately truth-heavy (70/30), unlike the other curated packs' 50/50 split.
    expect($pack->truths_count)->toBe(72);
    expect($pack->dares_count)->toBe(32);
    expect($pack->cards_count)->toBe(104);
    expect($pack->cards()->count())->toBe(104);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 103));
});

it('seeds guess the song starter with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'guess-the-song-starter')->firstOrFail();

    expect($pack->name)->toBe('Guess the Song Starter');
    expect($pack->truths_count)->toBe(52);
    expect($pack->dares_count)->toBe(52);
    expect($pack->cards_count)->toBe(104);
    expect($pack->cards()->count())->toBe(104);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 103));
});

it('seeds guess the movie starter with its curated 100-card deck', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $pack = Pack::query()->where('slug', 'guess-the-movie-starter')->firstOrFail();

    expect($pack->name)->toBe('Guess the Movie Starter');
    // The curated deck's first 2 truth / 2 dare entries intentionally match
    // the pack's 4 preview cards verbatim (matching their tone was the brief)
    // — seedCuratedCards() dedupes those against the preview set rather than
    // adding them twice, so 100 curated cards net 96 new + 4 preview = 100.
    expect($pack->truths_count)->toBe(50);
    expect($pack->dares_count)->toBe(50);
    expect($pack->cards_count)->toBe(100);
    expect($pack->cards()->count())->toBe(100);
    expect($pack->cards()->where('is_preview', true)->count())->toBe(4);

    $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');

    expect($positions->duplicates())->toBeEmpty();
    expect($positions->toArray())->toBe(range(0, 99));
});

it('syncs the randomized marketplace packs count metadata to their actual attached cards', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

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
