<?php

use App\Models\GameSession;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\Turn;
use App\Models\User;
use App\Policies\TurnPolicy;

it('allows an active party member other than the turn player to vote', function () {
    $party = Party::factory()->create();
    $gameSession = GameSession::factory()->create(['party_id' => $party->id]);
    $turnPlayer = User::factory()->create();
    $voter = User::factory()->create();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $voter->id]);
    $turn = Turn::factory()->create(['game_session_id' => $gameSession->id, 'user_id' => $turnPlayer->id]);

    expect((new TurnPolicy)->vote($voter, $turn))->toBeTrue();
});

it('forbids the turn player from voting on their own turn', function () {
    $party = Party::factory()->create();
    $gameSession = GameSession::factory()->create(['party_id' => $party->id]);
    $turnPlayer = User::factory()->create();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $turnPlayer->id]);
    $turn = Turn::factory()->create(['game_session_id' => $gameSession->id, 'user_id' => $turnPlayer->id]);

    expect((new TurnPolicy)->vote($turnPlayer, $turn))->toBeFalse();
});

it('forbids a former (left) party member from voting', function () {
    $party = Party::factory()->create();
    $gameSession = GameSession::factory()->create(['party_id' => $party->id]);
    $turnPlayer = User::factory()->create();
    $formerMember = User::factory()->create();
    PartyMember::factory()->left()->create(['party_id' => $party->id, 'user_id' => $formerMember->id]);
    $turn = Turn::factory()->create(['game_session_id' => $gameSession->id, 'user_id' => $turnPlayer->id]);

    expect((new TurnPolicy)->vote($formerMember, $turn))->toBeFalse();
});

it('forbids a non-member from voting', function () {
    $party = Party::factory()->create();
    $gameSession = GameSession::factory()->create(['party_id' => $party->id]);
    $turnPlayer = User::factory()->create();
    $outsider = User::factory()->create();
    $turn = Turn::factory()->create(['game_session_id' => $gameSession->id, 'user_id' => $turnPlayer->id]);

    expect((new TurnPolicy)->vote($outsider, $turn))->toBeFalse();
});
