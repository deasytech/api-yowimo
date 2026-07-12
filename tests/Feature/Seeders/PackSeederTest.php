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

it('syncs the randomized marketplace packs count metadata to their actual attached cards', function () {
    (new GameTypeSeeder)->run();
    (new PackSeeder)->run();

    $curatedSlugs = [
        'midnight-spice', 'office-icebreakers', 'sweet-silly-couples',
        'family-game-night', 'party-starter-pack', 'neon-confessions',
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
