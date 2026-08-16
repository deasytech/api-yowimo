<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Enums\PartyVisibility;
use App\Enums\WalletTransactionType;
use App\Events\GameCompleted;
use App\Events\PartyCreated;
use App\Events\PartyMemberJoined;
use App\Events\PartyStarted;
use App\Events\PurchaseCompleted;
use App\Events\RoundCompleted;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\TokenBundle;
use App\Models\User;
use App\Services\Game\GameSessionService;
use App\Services\Parties\PartyMembershipService;
use App\Services\Parties\PartyService;
use App\Services\Purchase\PackPurchaseService;
use App\Services\Purchase\PurchaseService;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\Event;

it('fires PartyCreated with the new party and its host', function () {
    Event::fake([PartyCreated::class]);

    $host = User::factory()->create();
    $party = app(PartyService::class)->create($host, [
        'title' => 'Event Test Party',
        'mode' => 'online',
        'visibility' => PartyVisibility::Public->value,
    ]);

    Event::assertDispatched(PartyCreated::class, fn ($event) => $event->partyId === $party->id && $event->hostId === $host->id);
});

it('fires PartyMemberJoined when a user joins, but not on an idempotent re-join', function () {
    Event::fake([PartyMemberJoined::class]);

    $party = Party::factory()->create(['status' => PartyStatus::Live, 'max_players' => 8, 'players_count' => 1]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $party->host_id]);
    $joiner = User::factory()->create();

    app(PartyMembershipService::class)->join($joiner, $party);

    Event::assertDispatched(PartyMemberJoined::class, fn ($event) => $event->partyId === $party->id && $event->userId === $joiner->id);

    Event::fake([PartyMemberJoined::class]);
    app(PartyMembershipService::class)->join($joiner, $party->fresh());

    Event::assertNotDispatched(PartyMemberJoined::class);
});

it('fires PartyStarted when a draft/scheduled party is started', function () {
    Event::fake([PartyStarted::class]);

    $party = Party::factory()->create(['status' => PartyStatus::Scheduled]);

    app(PartyMembershipService::class)->start($party);

    Event::assertDispatched(PartyStarted::class, fn ($event) => $event->partyId === $party->id);
});

it('fires WalletCredited on credit, but not on an idempotent replay', function () {
    Event::fake([WalletCredited::class]);

    $user = User::factory()->create();
    $transaction = app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp, idempotencyKey: 'evt_dispatch_1');

    Event::assertDispatched(WalletCredited::class, fn ($event) => $event->walletTransactionId === $transaction->id && $event->userId === $user->id);

    Event::fake([WalletCredited::class]);
    app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp, idempotencyKey: 'evt_dispatch_1');

    Event::assertNotDispatched(WalletCredited::class);
});

it('fires WalletDebited on debit', function () {
    Event::fake([WalletDebited::class]);

    $user = User::factory()->create();
    app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);
    $transaction = app(WalletService::class)->debit($user, 40, WalletTransactionType::Purchase);

    Event::assertDispatched(WalletDebited::class, fn ($event) => $event->walletTransactionId === $transaction->id && $event->userId === $user->id);
});

it('fires PurchaseCompleted for a token bundle purchase, but not on an idempotent replay', function () {
    Event::fake([PurchaseCompleted::class]);

    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create();

    $transaction = app(PurchaseService::class)->purchase($user, $bundle, 'evt_bundle_1');

    Event::assertDispatched(PurchaseCompleted::class, fn ($event) => $event->userId === $user->id
        && $event->referenceType === $bundle->getMorphClass()
        && $event->referenceId === $bundle->id
        && $event->walletTransactionId === $transaction->id);

    Event::fake([PurchaseCompleted::class]);
    app(PurchaseService::class)->purchase($user, $bundle, 'evt_bundle_1');

    Event::assertNotDispatched(PurchaseCompleted::class);
});

it('fires PurchaseCompleted for a pack purchase', function () {
    Event::fake([PurchaseCompleted::class]);

    $user = User::factory()->create();
    $pack = Pack::factory()->create(['price' => 50]);
    app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);

    $purchase = app(PackPurchaseService::class)->purchase($user, $pack, 'evt_pack_1');

    Event::assertDispatched(PurchaseCompleted::class, fn ($event) => $event->userId === $user->id
        && $event->referenceType === $pack->getMorphClass()
        && $event->referenceId === $pack->id
        && $event->walletTransactionId === $purchase->wallet_transaction_id);
});

it('fires RoundCompleted when every member has taken their turn for a round', function () {
    Event::fake([RoundCompleted::class]);

    $pack = Pack::factory()->create();
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 5);
    $round = $session->currentRound();

    $session = $service->nextTurn($session);

    Event::assertDispatched(RoundCompleted::class, fn ($event) => $event->gameSessionId === $session->id
        && $event->roundId === $round->id
        && $event->roundNumber === 1);
});

it('fires GameCompleted when the last round of the last turn completes', function () {
    Event::fake([GameCompleted::class]);

    $pack = Pack::factory()->create();
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    $session = $service->nextTurn($session);

    Event::assertDispatched(GameCompleted::class, fn ($event) => $event->gameSessionId === $session->id && $event->partyId === $party->id);
});
