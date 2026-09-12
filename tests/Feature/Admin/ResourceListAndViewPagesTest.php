<?php

use App\Models\GameType;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\TokenBundle;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->actingAs($this->admin, 'web');
});

it('renders the list and view pages for every catalog and audit resource', function () {
    // Legacy-style external URLs (not disk-relative paths) on the image
    // fields, since that's what real records look like and what the badge
    // color / image column closures need to survive without throwing.
    $gameType = GameType::factory()->create(['image_url' => 'https://example.com/game-type.jpg']);
    $pack = Pack::factory()->create(['game_type_id' => $gameType->id, 'cover_image_url' => 'https://example.com/pack.jpg']);
    $packCard = PackCard::factory()->create(['pack_id' => $pack->id]);
    $tokenBundle = TokenBundle::factory()->create();
    $user = User::factory()->create(['avatar_url' => 'https://example.com/avatar.jpg']);
    $party = Party::factory()->create(['host_id' => $user->id, 'game_type_id' => $gameType->id, 'pack_id' => $pack->id]);

    $resources = [
        'game-types' => $gameType,
        'packs' => $pack,
        'pack-cards' => $packCard,
        'token-bundles' => $tokenBundle,
        'users' => $user,
        'parties' => $party,
    ];

    foreach ($resources as $slug => $record) {
        $this->get("/admin/{$slug}")->assertOk();
        $this->get("/admin/{$slug}/{$record->getKey()}")->assertOk();
    }
});
