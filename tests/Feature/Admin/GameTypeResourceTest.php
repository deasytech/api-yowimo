<?php

use App\Filament\Resources\GameTypes\Pages\CreateGameType;
use App\Filament\Resources\GameTypes\Pages\EditGameType;
use App\Models\GameType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'password' => 'password',
        'is_admin' => true,
    ]);
    $this->actingAs($this->admin, 'web');
});

it('lets an admin create a game type', function () {
    Livewire::test(CreateGameType::class)
        ->fillForm([
            'slug' => 'test-game-type',
            'name' => 'Test Game Type',
            'intensity' => 'chill',
            'cost' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(GameType::where('slug', 'test-game-type')->exists())->toBeTrue();
});

it('lets an admin edit a game type', function () {
    $gameType = GameType::factory()->create(['name' => 'Original']);

    Livewire::test(EditGameType::class, ['record' => $gameType->getKey()])
        ->fillForm(['name' => 'Updated'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($gameType->refresh()->name)->toBe('Updated');
});

it('lets an admin delete a game type', function () {
    $gameType = GameType::factory()->create();

    Livewire::test(EditGameType::class, ['record' => $gameType->getKey()])
        ->callAction('delete');

    expect(GameType::find($gameType->id))->toBeNull();
});

it('preserves an existing external image_url when the image field is left untouched', function () {
    // Seeded/legacy records store arbitrary external URLs, not paths on the
    // local disk. FileUpload's default hydration drops any value that
    // doesn't `exists()` on disk, which would silently null this out on
    // save if ImageUploadField didn't disable that check.
    $gameType = GameType::factory()->create(['image_url' => 'https://example.com/cover.jpg']);

    Livewire::test(EditGameType::class, ['record' => $gameType->getKey()])
        ->fillForm(['name' => 'Renamed'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($gameType->refresh())
        ->name->toBe('Renamed')
        ->image_url->toBe('https://example.com/cover.jpg');
});

it('stores a newly uploaded image as a full public URL', function () {
    Storage::fake('public');

    $gameType = GameType::factory()->create(['image_url' => null]);

    Livewire::test(EditGameType::class, ['record' => $gameType->getKey()])
        ->fillForm(['image_url' => UploadedFile::fake()->image('cover.jpg')])
        ->call('save')
        ->assertHasNoFormErrors();

    $url = $gameType->refresh()->image_url;

    expect($url)->toStartWith(Storage::disk('public')->url('game-types'));
    expect(Storage::disk('public')->files('game-types'))->not->toBeEmpty();
});
