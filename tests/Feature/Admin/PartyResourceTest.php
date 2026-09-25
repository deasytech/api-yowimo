<?php

use App\Enums\PartyStatus;
use App\Filament\Resources\Parties\Pages\CreateParty;
use App\Filament\Resources\Parties\Pages\EditParty;
use App\Models\GameSession;
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

it('wraps create in a database transaction', function () {
    expect((new CreateParty)->hasDatabaseTransactions())->toBeTrue();
});

it('rejects more than 5 tags', function () {
    Livewire::test(CreateParty::class)
        ->fillForm([
            'host_id' => User::factory()->create()->id,
            'title' => 'Too many tags',
            'mode' => 'online',
            'visibility' => 'public',
            'status' => 'live',
            'tags' => ['one', 'two', 'three', 'four', 'five', 'six'],
        ])
        ->call('create')
        ->assertHasFormErrors(['tags']);
});

it('rejects a tag longer than 20 characters', function () {
    Livewire::test(CreateParty::class)
        ->fillForm([
            'host_id' => User::factory()->create()->id,
            'title' => 'Tag too long',
            'mode' => 'online',
            'visibility' => 'public',
            'status' => 'live',
            'tags' => [str_repeat('a', 21)],
        ])
        ->call('create')
        ->assertHasFormErrors(['tags.0']);
});

it('rejects a pack that does not belong to the selected game type', function () {
    $gameType = GameType::factory()->create();
    $otherGameType = GameType::factory()->create();
    $mismatchedPack = Pack::factory()->create(['game_type_id' => $otherGameType->id]);

    Livewire::test(CreateParty::class)
        ->fillForm([
            'host_id' => User::factory()->create()->id,
            'title' => 'Mismatched pack',
            'game_type_id' => $gameType->id,
            'pack_id' => $mismatchedPack->id,
            'mode' => 'online',
            'visibility' => 'public',
            'status' => 'live',
        ])
        ->call('create')
        ->assertHasFormErrors(['pack_id']);
});

it('locks the game type and pack once a game session exists, even if a change is submitted', function () {
    $gameType = GameType::factory()->create();
    $otherGameType = GameType::factory()->create();
    $pack = Pack::factory()->create(['game_type_id' => $gameType->id]);
    $otherPack = Pack::factory()->create(['game_type_id' => $otherGameType->id]);
    $party = Party::factory()->create(['game_type_id' => $gameType->id, 'pack_id' => $pack->id]);
    GameSession::factory()->create(['party_id' => $party->id]);

    Livewire::test(EditParty::class, ['record' => $party->getKey()])
        ->fillForm([
            'game_type_id' => $otherGameType->id,
            'pack_id' => $otherPack->id,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($party->refresh())
        ->game_type_id->toBe($gameType->id)
        ->pack_id->toBe($pack->id);
});

it('clears sponsor_name when is_sponsored is unchecked on create', function () {
    Livewire::test(CreateParty::class)
        ->fillForm([
            'host_id' => User::factory()->create()->id,
            'title' => 'Not actually sponsored',
            'mode' => 'online',
            'visibility' => 'public',
            'status' => 'live',
            'is_sponsored' => false,
            'sponsor_name' => 'Should be dropped',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $party = Party::where('title', 'Not actually sponsored')->firstOrFail();
    expect($party->sponsor_name)->toBeNull();
});

it('clears sponsor_name when is_sponsored is unchecked on edit', function () {
    $party = Party::factory()->create(['is_sponsored' => true, 'sponsor_name' => 'Acme Corp']);

    Livewire::test(EditParty::class, ['record' => $party->getKey()])
        ->fillForm(['is_sponsored' => false])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($party->refresh()->sponsor_name)->toBeNull();
});
