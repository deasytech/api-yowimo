<?php

use App\Models\Pack;
use App\Models\PackCard;
use Database\Seeders\GameTypeSeeder;
use Database\Seeders\TemptGamesSeeder;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    (new GameTypeSeeder)->run();
});

it('extracts games without any merge items and matches schema', function () {
    $jsonPath = database_path('data/tempt_extracted_games.json');
    expect(File::exists($jsonPath))->toBeTrue();

    $data = json_decode(File::get($jsonPath), true);
    expect($data)->toHaveKey('packs');
    expect($data['packs'])->toHaveCount(8);

    foreach ($data['packs'] as $pack) {
        expect($pack)->toHaveKeys(['name', 'pack_slug', 'game_type_slug', 'category', 'cards']);
        foreach ($pack['cards'] as $card) {
            expect(strtolower($card['text']))->not->toContain('merge');
        }
    }
});

it('syncs tempt games into database without duplicate cards or schema changes', function () {
    $seeder = new TemptGamesSeeder;
    $seeder->run();

    $expectedPacks = [
        'midnight-confessions-dares' => ['truths' => 150, 'dares' => 203, 'total' => 353],
        'never-have-i-ever-classic' => ['truths' => 99, 'dares' => 0, 'total' => 99],
        'charades-action-pack' => ['truths' => 0, 'dares' => 100, 'total' => 100],
        'deep-conversations-couples' => ['truths' => 57, 'dares' => 0, 'total' => 57],
        'icebreaker-social' => ['truths' => 100, 'dares' => 0, 'total' => 100],
        'drink-up-mixer' => ['truths' => 0, 'dares' => 99, 'total' => 99],
        'spell-or-sip' => ['truths' => 0, 'dares' => 100, 'total' => 100],
        'spicy-quick-think-blitz' => ['truths' => 100, 'dares' => 0, 'total' => 100],
    ];

    foreach ($expectedPacks as $slug => $counts) {
        $pack = Pack::query()->where('slug', $slug)->first();
        expect($pack)->not->toBeNull();
        expect($pack->truths_count)->toBe($counts['truths']);
        expect($pack->dares_count)->toBe($counts['dares']);
        expect($pack->cards_count)->toBe($counts['total']);
        expect($pack->cards()->count())->toBe($counts['total']);

        // Check card positions are continuous and non-colliding
        $positions = PackCard::query()->where('pack_id', $pack->id)->orderBy('position')->pluck('position');
        expect($positions->duplicates())->toBeEmpty();
        expect($positions->toArray())->toBe(range(0, $counts['total'] - 1));
    }

    // Running the seeder again should be idempotent: no duplicate cards,
    // and the persisted catalog order and counts must not shift.
    $baseline = [];
    foreach ($expectedPacks as $slug => $counts) {
        $pack = Pack::query()->where('slug', $slug)->first();
        $baseline[$slug] = ['sort_order' => $pack->sort_order, 'cards_count' => $pack->cards_count];
    }

    $seeder->run();
    foreach ($expectedPacks as $slug => $counts) {
        $pack = Pack::query()->where('slug', $slug)->first();
        expect($pack->cards()->count())->toBe($counts['total']);
        expect($pack->sort_order)->toBe($baseline[$slug]['sort_order']);
        expect($pack->cards_count)->toBe($baseline[$slug]['cards_count']);
    }
});

it('supports artisan dry-run without writing to database', function () {
    $existingPacksCount = Pack::query()->count();

    $this->artisan('yowimo:sync-tempt-games --dry-run')
        ->assertSuccessful();

    expect(Pack::query()->count())->toBe($existingPacksCount);
});

it('fails the sync command when the extracted file has no packs array', function () {
    $path = sys_get_temp_dir().'/tempt-missing-packs-'.uniqid().'.json';
    File::put($path, json_encode(['games' => ['not' => 'packs']]));

    // Invalid input must fail the command in every mode, including dry-run,
    // instead of reporting an empty "successful" sync.
    $this->artisan('yowimo:sync-tempt-games --path='.$path)
        ->assertFailed()
        ->expectsOutputToContain("missing top-level 'packs' array");

    $this->artisan('yowimo:sync-tempt-games --dry-run --path='.$path)
        ->assertFailed();

    expect(Pack::query()->count())->toBe(0);

    File::delete($path);
});

it('skips only the literal merge placeholder and keeps ordinary words containing merge', function () {
    $path = sys_get_temp_dir().'/tempt-merge-placeholder-'.uniqid().'.json';
    File::put($path, json_encode([
        'packs' => [
            [
                'name' => 'Placeholder Test',
                'pack_slug' => 'placeholder-test-pack',
                'game_type_slug' => 'truth-dare',
                'category' => 'limited',
                'cards' => [
                    ['text' => 'merge', 'kind' => 'truth'],
                    ['text' => '  MERGE  ', 'kind' => 'truth'],
                    ['text' => '   ', 'kind' => 'truth'],
                    ['text' => 'In case of emergency, compliment the person to your left.', 'kind' => 'truth'],
                    ['text' => 'Perform a submerged impression of a seagull.', 'kind' => 'dare'],
                ],
            ],
        ],
    ]));

    (new TemptGamesSeeder)->run($path);

    $pack = Pack::query()->where('slug', 'placeholder-test-pack')->first();
    expect($pack)->not->toBeNull();
    expect($pack->cards()->count())->toBe(2);
    expect($pack->cards()->pluck('text')->all())->toBe([
        'In case of emergency, compliment the person to your left.',
        'Perform a submerged impression of a seagull.',
    ]);

    File::delete($path);
});
