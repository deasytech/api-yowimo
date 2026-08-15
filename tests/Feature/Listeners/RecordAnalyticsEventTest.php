<?php

use App\Events\PartyCreated;
use App\Listeners\RecordAnalyticsEvent;
use App\Models\User;
use App\Services\Parties\PartyService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

it('actually runs the analytics listener through a real queue worker', function () {
    config(['queue.default' => 'database']);

    $host = User::factory()->create();
    $party = app(PartyService::class)->create($host, [
        'title' => 'Real Queue Party',
        'mode' => 'online',
        'visibility' => 'public',
    ]);

    expect(DB::table('jobs')->count())->toBe(1);

    Log::shouldReceive('info')
        ->once()
        ->with('analytics.event_recorded', [
            'event' => PartyCreated::class,
            'party_id' => $party->id,
            'host_id' => $host->id,
        ]);

    $this->artisan('queue:work', ['--once' => true])->run();

    expect(DB::table('jobs')->count())->toBe(0);
});
