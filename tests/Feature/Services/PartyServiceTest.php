<?php

use App\Enums\PartyMode;
use App\Enums\PartyVisibility;
use App\Models\GameType;
use App\Models\Party;
use App\Models\User;
use App\Services\Parties\PartyService;
use App\Services\Parties\RoomCodeGenerator;
use Illuminate\Database\QueryException;

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
