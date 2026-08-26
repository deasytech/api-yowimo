<?php

use App\Filament\Resources\GameTypes\Pages\CreateGameType;
use App\Filament\Resources\GameTypes\Pages\EditGameType;
use App\Models\GameType;
use App\Models\User;
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
