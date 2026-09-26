<?php

use App\Filament\Resources\AnalyticsEvents\AnalyticsEventResource;
use App\Filament\Resources\Badges\BadgeResource;
use App\Filament\Resources\Friendships\FriendshipResource;
use App\Filament\Resources\GameSessions\GameSessionResource;
use App\Filament\Resources\Notifications\NotificationResource;
use App\Filament\Resources\PackPurchases\PackPurchaseResource;
use App\Filament\Resources\PartyLikes\PartyLikeResource;
use App\Filament\Resources\PartyMembers\PartyMemberResource;
use App\Filament\Resources\PaymentMethods\PaymentMethodResource;
use App\Filament\Resources\PushTokens\PushTokenResource;
use App\Filament\Resources\Rounds\RoundResource;
use App\Filament\Resources\Turns\TurnResource;
use App\Filament\Resources\UserBadges\UserBadgeResource;
use App\Filament\Resources\Votes\VoteResource;
use App\Filament\Resources\Wallets\WalletResource;
use App\Filament\Resources\WebhookEvents\WebhookEventResource;
use App\Filament\Resources\XpTransactions\XpTransactionResource;
use App\Models\AnalyticsEvent;
use App\Models\Badge;
use App\Models\Friendship;
use App\Models\GameSession;
use App\Models\Notification;
use App\Models\PackPurchase;
use App\Models\PartyLike;
use App\Models\PartyMember;
use App\Models\PaymentMethod;
use App\Models\PushToken;
use App\Models\Round;
use App\Models\Turn;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\Vote;
use App\Models\Wallet;
use App\Models\WebhookEvent;
use App\Models\XpTransaction;

/**
 * Every resource in this list is audit/read-only: none of them may be
 * created, edited, or deleted from the panel.
 *
 * @return array<string, array{class-string, class-string}>
 */
function readOnlyPanelResources(): array
{
    return [
        'party-members' => [PartyMemberResource::class, PartyMember::class],
        'friendships' => [FriendshipResource::class, Friendship::class],
        'party-likes' => [PartyLikeResource::class, PartyLike::class],
        'wallets' => [WalletResource::class, Wallet::class],
        'payment-methods' => [PaymentMethodResource::class, PaymentMethod::class],
        'pack-purchases' => [PackPurchaseResource::class, PackPurchase::class],
        'game-sessions' => [GameSessionResource::class, GameSession::class],
        'rounds' => [RoundResource::class, Round::class],
        'turns' => [TurnResource::class, Turn::class],
        'votes' => [VoteResource::class, Vote::class],
        'user-badges' => [UserBadgeResource::class, UserBadge::class],
        'xp-transactions' => [XpTransactionResource::class, XpTransaction::class],
        'notifications' => [NotificationResource::class, Notification::class],
        'push-tokens' => [PushTokenResource::class, PushToken::class],
        'analytics-events' => [AnalyticsEventResource::class, AnalyticsEvent::class],
        'webhook-events' => [WebhookEventResource::class, WebhookEvent::class],
    ];
}

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

it('never allows creating, editing, or deleting audit records from the panel', function () {
    foreach (readOnlyPanelResources() as [$resource, $model]) {
        $record = new $model;

        expect($resource::canCreate())->toBeFalse("{$resource} should not allow create")
            ->and($resource::canEdit($record))->toBeFalse("{$resource} should not allow edit")
            ->and($resource::canDelete($record))->toBeFalse("{$resource} should not allow delete")
            ->and($resource::canDeleteAny())->toBeFalse("{$resource} should not allow bulk delete");
    }
});

it('does not register create or edit routes for the audit resources', function () {
    $this->actingAs($this->admin, 'web');

    foreach (array_keys(readOnlyPanelResources()) as $slug) {
        $this->get("/admin/{$slug}/create")->assertNotFound();
        $this->get("/admin/{$slug}/1/edit")->assertNotFound();
    }
});

it('lets an admin view any wallet even though the API policy is owner-only', function () {
    expect(WalletResource::canView(new Wallet))->toBeTrue();

    $wallet = Wallet::factory()->create([
        'user_id' => User::factory()->create()->id,
    ]);

    $this->actingAs($this->admin, 'web')
        ->get("/admin/wallets/{$wallet->getKey()}")
        ->assertOk();
});

it('lets admins manage badges but never delete them', function () {
    expect(BadgeResource::canCreate())->toBeTrue();

    $badge = Badge::factory()->create();

    expect(BadgeResource::canDelete($badge))->toBeFalse()
        ->and(BadgeResource::canDeleteAny())->toBeFalse();

    $this->actingAs($this->admin, 'web')
        ->get('/admin/badges/create')
        ->assertOk();

    $this->actingAs($this->admin, 'web')
        ->get("/admin/badges/{$badge->getKey()}/edit")
        ->assertOk();
});
