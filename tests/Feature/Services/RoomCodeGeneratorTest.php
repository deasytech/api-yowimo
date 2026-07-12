<?php

use App\Models\GameType;
use App\Models\Party;
use App\Models\User;
use App\Services\Parties\RoomCodeGenerator;

it('generates a 6 character uppercase alphanumeric code without ambiguous characters', function () {
    $code = app(RoomCodeGenerator::class)->generate();

    expect($code)->toHaveLength(6);
    expect($code)->toMatch('/^[ABCDEFGHJKLMNPQRSTUVWXYZ23456789]{6}$/');
});

it('never returns a code already taken by an existing party', function () {
    $generator = app(RoomCodeGenerator::class);
    $host = User::factory()->create();
    $gameType = GameType::factory()->create();

    $existing = Party::factory()->create([
        'host_id' => $host->id,
        'game_type_id' => $gameType->id,
        'room_code' => $generator->generate(),
    ]);

    $next = $generator->generate();

    expect($next)->not->toBe($existing->room_code);
});

it('generates distinct codes across repeated calls', function () {
    $generator = app(RoomCodeGenerator::class);
    $host = User::factory()->create();
    $gameType = GameType::factory()->create();

    $codes = collect(range(1, 20))->map(function () use ($generator, $host, $gameType) {
        $code = $generator->generate();
        Party::factory()->create([
            'host_id' => $host->id,
            'game_type_id' => $gameType->id,
            'room_code' => $code,
        ]);

        return $code;
    });

    expect($codes->unique())->toHaveCount(20);
});
