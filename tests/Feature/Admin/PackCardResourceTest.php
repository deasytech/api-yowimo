<?php

use App\Enums\PackCardKind;
use App\Filament\Resources\PackCards\Pages\ListPackCards;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\User;
use Filament\Tables\Filters\SelectFilter;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin, 'web');
});

it('offers a pack filter built from the pack relationship', function () {
    Pack::factory()->create(['name' => 'Spicy Night']);

    Livewire::test(ListPackCards::class)
        ->assertTableFilterExists('pack', fn (SelectFilter $filter) => $filter->getRelationshipName() === 'pack'
            && $filter->getRelationshipTitleAttribute() === 'name');
});

it('filters pack cards by pack', function () {
    $spicyPack = Pack::factory()->create(['name' => 'Spicy Night']);
    $chillPack = Pack::factory()->create(['name' => 'Chill Evening']);

    $spicyCard = PackCard::factory()->create([
        'pack_id' => $spicyPack->id,
        'kind' => PackCardKind::Truth,
        'text' => 'Spicy truth?',
    ]);
    $chillTruth = PackCard::factory()->create([
        'pack_id' => $chillPack->id,
        'kind' => PackCardKind::Truth,
        'text' => 'Chill truth?',
    ]);
    $chillDare = PackCard::factory()->create([
        'pack_id' => $chillPack->id,
        'kind' => PackCardKind::Dare,
        'text' => 'Chill dare.',
    ]);

    Livewire::test(ListPackCards::class)
        ->assertCanSeeTableRecords([$spicyCard, $chillTruth, $chillDare])
        ->filterTable('pack', $spicyPack->getKey())
        ->assertCanSeeTableRecords([$spicyCard])
        ->assertCanNotSeeTableRecords([$chillTruth, $chillDare]);
});

it('combines the pack filter with the kind filter', function () {
    $spicyPack = Pack::factory()->create(['name' => 'Spicy Night']);
    $chillPack = Pack::factory()->create(['name' => 'Chill Evening']);

    $spicyTruth = PackCard::factory()->create([
        'pack_id' => $spicyPack->id,
        'kind' => PackCardKind::Truth,
        'text' => 'Spicy truth?',
    ]);
    PackCard::factory()->create([
        'pack_id' => $spicyPack->id,
        'kind' => PackCardKind::Dare,
        'text' => 'Spicy dare.',
    ]);
    PackCard::factory()->create([
        'pack_id' => $chillPack->id,
        'kind' => PackCardKind::Truth,
        'text' => 'Chill truth?',
    ]);

    Livewire::test(ListPackCards::class)
        ->filterTable('pack', $spicyPack->getKey())
        ->filterTable('kind', 'truth')
        ->assertCanSeeTableRecords([$spicyTruth])
        ->assertCanNotSeeTableRecords(PackCard::whereKeyNot($spicyTruth->getKey())->get());
});

it('resets the pack filter and shows every card again', function () {
    $spicyPack = Pack::factory()->create(['name' => 'Spicy Night']);
    $chillPack = Pack::factory()->create(['name' => 'Chill Evening']);

    $spicyCard = PackCard::factory()->create(['pack_id' => $spicyPack->id]);
    $chillCard = PackCard::factory()->create(['pack_id' => $chillPack->id]);

    Livewire::test(ListPackCards::class)
        ->filterTable('pack', $spicyPack->getKey())
        ->assertCanNotSeeTableRecords([$chillCard])
        ->resetTableFilters()
        ->assertCanSeeTableRecords([$spicyCard, $chillCard]);
});

it('filters pack cards for the pack chosen in the dropdown', function () {
    $spicyPack = Pack::factory()->create(['name' => 'Spicy Night']);
    $chillPack = Pack::factory()->create(['name' => 'Chill Evening']);

    $spicyCard = PackCard::factory()->create(['pack_id' => $spicyPack->id]);

    Livewire::test(ListPackCards::class)
        ->filterTable('pack', $chillPack->getKey())
        ->assertCanNotSeeTableRecords([$spicyCard])
        ->assertSet('tableFilters.pack.value', $chillPack->getKey());
});
