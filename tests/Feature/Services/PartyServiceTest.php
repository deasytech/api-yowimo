<?php

use App\Enums\PartyMode;
use App\Enums\PartyVisibility;
use App\Enums\WalletTransactionType;
use App\Exceptions\Api\InsufficientWalletBalanceException;
use App\Exceptions\Api\PackNotInGameTypeException;
use App\Exceptions\Api\PartyCoverImageUploadException;
use App\Models\GameType;
use App\Models\Pack;
use App\Models\Party;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Parties\PartyCoverImageService;
use App\Services\Parties\PartyService;
use App\Services\Parties\RoomCodeGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('retries once with a new room code when it loses a race to a duplicate', function () {
    $host = User::factory()->create();
    $gameType = GameType::factory()->create();

    $existing = Party::factory()->create(['room_code' => 'DUPE01']);

    $fakeGenerator = new class extends RoomCodeGenerator
    {
        private int $calls = 0;

        public function generate(): string
        {
            $this->calls++;

            return $this->calls === 1 ? 'DUPE01' : 'FRESH1';
        }
    };

    $this->app->instance(RoomCodeGenerator::class, $fakeGenerator);

    $party = app(PartyService::class)->create($host, [
        'title' => 'Race Condition Party',
        'game_type_id' => $gameType->id,
        'mode' => PartyMode::Online->value,
        'visibility' => PartyVisibility::Public->value,
    ]);

    expect($party->room_code)->toBe('FRESH1');
    expect($party->id)->not->toBe($existing->id);
});

it('does not double-charge the party entry cost on a room-code collision retry', function () {
    $host = User::factory()->create();
    Wallet::factory()->create(['user_id' => $host->id, 'balance' => 100]);
    WalletTransaction::factory()->create(['wallet_id' => $host->wallet->id, 'amount' => 100, 'balance_after' => 100]);
    $gameType = GameType::factory()->create(['cost' => 30]);

    Party::factory()->create(['room_code' => 'DUPE02']);

    $fakeGenerator = new class extends RoomCodeGenerator
    {
        private int $calls = 0;

        public function generate(): string
        {
            $this->calls++;

            return $this->calls === 1 ? 'DUPE02' : 'FRESH2';
        }
    };

    $this->app->instance(RoomCodeGenerator::class, $fakeGenerator);

    app(PartyService::class)->create($host, [
        'title' => 'Paid Race Condition Party',
        'game_type_id' => $gameType->id,
        'mode' => PartyMode::Online->value,
        'visibility' => PartyVisibility::Public->value,
    ]);

    expect($host->wallet->fresh()->balance)->toBe(70);
    expect(WalletTransaction::where('wallet_id', $host->wallet->id)->where('type', WalletTransactionType::PartyEntry)->count())->toBe(1);
});

it('rejects party creation when the host cannot afford the game types entry cost, without persisting the party', function () {
    $host = User::factory()->create();
    $gameType = GameType::factory()->create(['cost' => 30]);

    expect(fn () => app(PartyService::class)->create($host, [
        'title' => 'Unaffordable Service Party',
        'game_type_id' => $gameType->id,
        'mode' => PartyMode::Online->value,
        'visibility' => PartyVisibility::Public->value,
    ]))->toThrow(InsufficientWalletBalanceException::class);

    expect(Party::where('title', 'Unaffordable Service Party')->exists())->toBeFalse();
});

it('lets non-duplicate database errors propagate unchanged', function () {
    $host = User::factory()->create();

    // A non-existent game_type_id triggers a real FK violation, which must
    // not be mistaken for a room_code collision and swallowed by the retry.
    expect(fn () => app(PartyService::class)->create($host, [
        'title' => 'Bad Foreign Key Party',
        'game_type_id' => 999999,
        'mode' => PartyMode::Online->value,
        'visibility' => PartyVisibility::Public->value,
    ]))->toThrow(QueryException::class);
});

it('deletes the uploaded cover image when party creation fails after the upload succeeds', function () {
    Storage::fake('public');
    $host = User::factory()->create();

    expect(fn () => app(PartyService::class)->create($host, [
        'title' => 'Bad Foreign Key Party',
        'game_type_id' => 999999,
        'mode' => PartyMode::Online->value,
        'visibility' => PartyVisibility::Public->value,
    ], UploadedFile::fake()->image('cover.jpg')))->toThrow(QueryException::class);

    expect(Storage::disk('public')->allFiles('parties'))->toBeEmpty();
});

it('rejects creating a party whose pack does not belong to its game type', function () {
    $host = User::factory()->create();
    $gameType = GameType::factory()->create();
    $otherGameType = GameType::factory()->create();
    $pack = Pack::factory()->create(['game_type_id' => $otherGameType->id]);

    expect(fn () => app(PartyService::class)->create($host, [
        'title' => 'Mismatched Party',
        'game_type_id' => $gameType->id,
        'pack_id' => $pack->id,
        'mode' => PartyMode::Online->value,
        'visibility' => PartyVisibility::Public->value,
    ]))->toThrow(PackNotInGameTypeException::class);

    expect(Party::where('title', 'Mismatched Party')->exists())->toBeFalse();
});

it('allows creating a party whose pack matches its game type', function () {
    $host = User::factory()->create();
    $gameType = GameType::factory()->create();
    $pack = Pack::factory()->create(['game_type_id' => $gameType->id]);

    $party = app(PartyService::class)->create($host, [
        'title' => 'Matching Party',
        'game_type_id' => $gameType->id,
        'pack_id' => $pack->id,
        'mode' => PartyMode::Online->value,
        'visibility' => PartyVisibility::Public->value,
    ]);

    expect($party->pack_id)->toBe($pack->id);
});

it('inherits the game type default pack when creating a party without one', function () {
    $host = User::factory()->create();
    $gameType = GameType::factory()->create();
    $defaultPack = Pack::factory()->create(['game_type_id' => $gameType->id]);
    $gameType->update(['default_pack_id' => $defaultPack->id]);

    $party = app(PartyService::class)->create($host, [
        'title' => 'Inherited Deck Party',
        'game_type_id' => $gameType->id,
        'mode' => PartyMode::Online->value,
        'visibility' => PartyVisibility::Public->value,
    ]);

    expect($party->pack_id)->toBe($defaultPack->id);
});

it('leaves the pack empty when creating a party for a game type with no packs', function () {
    $host = User::factory()->create();
    $gameType = GameType::factory()->create();

    $party = app(PartyService::class)->create($host, [
        'title' => 'Deckless Party',
        'game_type_id' => $gameType->id,
        'mode' => PartyMode::Online->value,
        'visibility' => PartyVisibility::Public->value,
    ]);

    expect($party->pack_id)->toBeNull();
});

it('rejects updating a party to a pack that does not belong to its current game type', function () {
    $gameType = GameType::factory()->create();
    $otherGameType = GameType::factory()->create();
    $mismatchedPack = Pack::factory()->create(['game_type_id' => $otherGameType->id]);
    $party = Party::factory()->create(['game_type_id' => $gameType->id, 'pack_id' => null]);

    expect(fn () => app(PartyService::class)->update($party, ['pack_id' => $mismatchedPack->id]))
        ->toThrow(PackNotInGameTypeException::class);

    expect($party->fresh()->pack_id)->toBeNull();
});

it('re-resolves the pack when the game type changes to one its current pack does not belong to', function () {
    $gameType = GameType::factory()->create();
    $otherGameType = GameType::factory()->create();
    $pack = Pack::factory()->create(['game_type_id' => $gameType->id]);
    $otherDefaultPack = Pack::factory()->create(['game_type_id' => $otherGameType->id]);
    $otherGameType->update(['default_pack_id' => $otherDefaultPack->id]);
    $party = Party::factory()->create(['game_type_id' => $gameType->id, 'pack_id' => $pack->id]);

    app(PartyService::class)->update($party, ['game_type_id' => $otherGameType->id]);

    expect($party->fresh()->game_type_id)->toBe($otherGameType->id);
    expect($party->fresh()->pack_id)->toBe($otherDefaultPack->id);
});

it('allows updating both game type and pack together when they match each other', function () {
    $gameType = GameType::factory()->create();
    $pack = Pack::factory()->create(['game_type_id' => $gameType->id]);
    $party = Party::factory()->create(['game_type_id' => null, 'pack_id' => null]);

    $updated = app(PartyService::class)->update($party, [
        'game_type_id' => $gameType->id,
        'pack_id' => $pack->id,
    ]);

    expect($updated->game_type_id)->toBe($gameType->id);
    expect($updated->pack_id)->toBe($pack->id);
});

it('throws when a party cover image upload fails to store', function () {
    Storage::shouldReceive('disk')->with('public')->andReturnSelf();
    Storage::shouldReceive('put')->andReturn(false);

    expect(fn () => app(PartyCoverImageService::class)->store(UploadedFile::fake()->image('cover.jpg')))
        ->toThrow(PartyCoverImageUploadException::class);
});

it('throws when an unsupported image type is submitted directly to the cover image service', function () {
    expect(fn () => app(PartyCoverImageService::class)->store(UploadedFile::fake()->create('malicious.svg', 10, 'image/svg+xml')))
        ->toThrow(PartyCoverImageUploadException::class);
});
