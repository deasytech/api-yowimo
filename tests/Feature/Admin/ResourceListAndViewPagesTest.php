<?php

use App\Enums\PartyVisibility;
use App\Models\AnalyticsEvent;
use App\Models\Badge;
use App\Models\Friendship;
use App\Models\GameSession;
use App\Models\GameType;
use App\Models\Notification;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\PackPurchase;
use App\Models\Party;
use App\Models\PartyLike;
use App\Models\PartyMember;
use App\Models\PaymentMethod;
use App\Models\PushToken;
use App\Models\Round;
use App\Models\TokenBundle;
use App\Models\Turn;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\Vote;
use App\Models\Wallet;
use App\Models\WebhookEvent;
use App\Models\XpTransaction;

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
    // Pinned public: PartyPolicy::view() (also used to authorize this admin
    // page) only allows a non-host viewer to see public parties, and the
    // factory otherwise randomizes visibility.
    $party = Party::factory()->create(['host_id' => $user->id, 'game_type_id' => $gameType->id, 'pack_id' => $pack->id, 'visibility' => PartyVisibility::Public]);

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

it('renders the list and view pages for every resource added since the first pass', function () {
    $resources = [
        'party-members' => PartyMember::factory()->create(),
        'friendships' => Friendship::factory()->create(),
        'party-likes' => PartyLike::factory()->create(),
        'wallets' => Wallet::factory()->create(),
        'payment-methods' => PaymentMethod::factory()->create(),
        'pack-purchases' => PackPurchase::factory()->create(),
        'game-sessions' => GameSession::factory()->create(),
        'rounds' => Round::factory()->create(),
        'turns' => Turn::factory()->create(),
        'votes' => Vote::factory()->create(),
        'badges' => Badge::factory()->create(),
        'user-badges' => UserBadge::factory()->create(),
        'xp-transactions' => XpTransaction::factory()->create(),
        'notifications' => Notification::factory()->create(),
        'push-tokens' => PushToken::factory()->create(),
        'analytics-events' => AnalyticsEvent::factory()->create(),
        'webhook-events' => WebhookEvent::factory()->create(),
    ];

    foreach ($resources as $slug => $record) {
        $this->get("/admin/{$slug}")->assertOk();
        $this->get("/admin/{$slug}/{$record->getKey()}")->assertOk();
    }
});
