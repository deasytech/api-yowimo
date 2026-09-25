<?php

use App\Enums\PartyStatus;
use App\Filament\Resources\Parties\Pages\CreateParty;
use App\Filament\Resources\Parties\Pages\EditParty;
use App\Models\GameType;
use App\Models\Pack;
use App\Models\Party;
use App\Models\PartyMember;
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

it('lets an admin create a party, generating a room code and the host membership', function () {
    $host = User::factory()->create();
    $gameType = GameType::factory()->create();
    $pack = Pack::factory()->create(['game_type_id' => $gameType->id]);

    Livewire::test(CreateParty::class)
        ->fillForm([
            'host_id' => $host->id,
            'title' => 'Admin-created party',
            'game_type_id' => $gameType->id,
            'pack_id' => $pack->id,
            'mode' => 'online',
            'visibility' => 'public',
            'status' => 'live',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $party = Party::where('title', 'Admin-created party')->firstOrFail();

    expect($party->host_id)->toBe($host->id);
    expect($party->room_code)->toMatch('/^[A-Z2-9]{6}$/');
    expect($party->players_count)->toBe(1);
    expect(PartyMember::where('party_id', $party->id)->where('user_id', $host->id)->exists())->toBeTrue();
});

it('lets an admin edit a party', function () {
    $party = Party::factory()->create(['title' => 'Original title', 'status' => 'live']);

    Livewire::test(EditParty::class, ['record' => $party->getKey()])
        ->fillForm(['title' => 'Updated title', 'status' => 'ended'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($party->refresh())
        ->title->toBe('Updated title')
        ->status->toBe(PartyStatus::Ended);
});

it('stores a newly uploaded cover image as a full public URL', function () {
    Storage::fake('public');
    $party = Party::factory()->create(['cover_image_url' => null]);

    Livewire::test(EditParty::class, ['record' => $party->getKey()])
        ->fillForm(['cover_image_url' => UploadedFile::fake()->image('cover.jpg')])
        ->call('save')
        ->assertHasNoFormErrors();

    $url = $party->refresh()->cover_image_url;

    expect($url)->toStartWith(Storage::disk('public')->url('parties'));
    expect(Storage::disk('public')->files('parties'))->not->toBeEmpty();
});

it('rejects a cover image upload with an unsupported mime type', function () {
    Storage::fake('public');
    $party = Party::factory()->create(['cover_image_url' => null]);

    Livewire::test(EditParty::class, ['record' => $party->getKey()])
        ->fillForm(['cover_image_url' => UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml')])
        ->call('save')
        ->assertHasFormErrors(['cover_image_url']);

    expect($party->refresh()->cover_image_url)->toBeNull();
});
