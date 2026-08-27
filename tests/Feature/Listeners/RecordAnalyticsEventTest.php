<?php

use App\Events\PartyMemberJoined;
use App\Events\PartyStarted;
use App\Events\PurchaseCompleted;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Listeners\RecordAnalyticsEvent;
use App\Models\AnalyticsEvent;
use App\Models\User;
use App\Services\Parties\PartyService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

it('pushes the analytics listener onto the queue when PartyCreated fires', function () {
    Queue::fake();

    $host = User::factory()->create();
    app(PartyService::class)->create($host, [
        'title' => 'Queue Proof Party',
        'mode' => 'online',
        'visibility' => 'public',
    ]);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === RecordAnalyticsEvent::class);
});

it('actually persists an analytics_events row through a real queue worker', function () {
    config(['queue.default' => 'database']);

    $host = User::factory()->create();
    $party = app(PartyService::class)->create($host, [
        'title' => 'Real Queue Party',
        'mode' => 'online',
        'visibility' => 'public',
    ]);

    expect(DB::table('jobs')->count())->toBe(1);

    $this->artisan('queue:work', ['--once' => true])->run();

    expect(DB::table('jobs')->count())->toBe(0);

    expect(AnalyticsEvent::query()->where('event', 'PartyCreated')->first())
        ->not->toBeNull()
        ->user_id->toBe($host->id)
        ->payload->toBe(['party_id' => $party->id, 'host_id' => $host->id]);
});

it('persists a PartyMemberJoined analytics row', function () {
    $user = User::factory()->create();
    $event = new PartyMemberJoined(partyId: 42, userId: $user->id);

    app(RecordAnalyticsEvent::class)->handle($event);

    expect(AnalyticsEvent::query()->where('event', 'PartyMemberJoined')->first())
        ->not->toBeNull()
        ->user_id->toBe($user->id)
        ->payload->toBe(['party_id' => 42, 'user_id' => $user->id]);
});

it('persists a PartyStarted analytics row with no user', function () {
    app(RecordAnalyticsEvent::class)->handle(new PartyStarted(partyId: 7));

    expect(AnalyticsEvent::query()->where('event', 'PartyStarted')->first())
        ->not->toBeNull()
        ->user_id->toBeNull()
        ->payload->toBe(['party_id' => 7]);
});

it('persists a WalletCredited analytics row', function () {
    $user = User::factory()->create();

    app(RecordAnalyticsEvent::class)->handle(new WalletCredited(walletTransactionId: 99, userId: $user->id));

    expect(AnalyticsEvent::query()->where('event', 'WalletCredited')->first())
        ->not->toBeNull()
        ->user_id->toBe($user->id)
        ->payload->toBe(['wallet_transaction_id' => 99, 'user_id' => $user->id]);
});

it('persists a WalletDebited analytics row', function () {
    $user = User::factory()->create();

    app(RecordAnalyticsEvent::class)->handle(new WalletDebited(walletTransactionId: 100, userId: $user->id));

    expect(AnalyticsEvent::query()->where('event', 'WalletDebited')->first())
        ->not->toBeNull()
        ->user_id->toBe($user->id)
        ->payload->toBe(['wallet_transaction_id' => 100, 'user_id' => $user->id]);
});

it('persists a PurchaseCompleted analytics row', function () {
    $user = User::factory()->create();

    app(RecordAnalyticsEvent::class)->handle(new PurchaseCompleted(
        userId: $user->id,
        referenceType: 'pack',
        referenceId: 5,
        walletTransactionId: 101,
    ));

    expect(AnalyticsEvent::query()->where('event', 'PurchaseCompleted')->first())
        ->not->toBeNull()
        ->user_id->toBe($user->id)
        ->payload->toBe([
            'user_id' => $user->id,
            'reference_type' => 'pack',
            'reference_id' => 5,
            'wallet_transaction_id' => 101,
        ]);
});
