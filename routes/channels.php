<?php

use App\Models\GameSession;
use App\Models\PartyMember;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// This API authenticates exclusively via the "clerk" guard (Bearer JWT, no
// sessions) — every channel below must resolve its user through it explicitly,
// since the app's default guard ("web") never has an authenticated user.
$clerkGuard = ['guards' => ['clerk']];

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, $clerkGuard);

Broadcast::channel('party.{partyId}', function (User $user, int $partyId) {
    $isMember = PartyMember::query()->where('party_id', $partyId)->where('user_id', $user->id)->exists();

    if (! $isMember) {
        return null;
    }

    return [
        'id' => $user->id,
        'display_name' => $user->display_name,
        'username' => $user->username,
    ];
}, $clerkGuard);

Broadcast::channel('game-session.{gameSessionId}', function (User $user, int $gameSessionId) {
    $session = GameSession::query()->find($gameSessionId);

    if (! $session) {
        return false;
    }

    return PartyMember::query()->where('party_id', $session->party_id)->where('user_id', $user->id)->exists();
}, $clerkGuard);
