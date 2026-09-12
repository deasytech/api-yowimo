<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Events\AiHostMessageSent;
use App\Events\RoundCompleted;
use App\Listeners\SendAiHostMessage;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Services\AI\AIProvider;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

/**
 * @return array{0: User, 1: Party}
 */
function createLiveSoloGameSessionForAiHost(): array
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    return [$host, $party];
}

it('pushes the AI host message listener onto the queue when GameCompleted fires', function () {
    [$host, $party] = createLiveSoloGameSessionForAiHost();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Queue::fake();

    $service->nextTurn($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendAiHostMessage::class);
});

it('broadcasts an AI host message into the game session channel when GameCompleted fires', function () {
    [$host, $party] = createLiveSoloGameSessionForAiHost();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    $provider = Mockery::mock(AIProvider::class);
    $provider->shouldReceive('respond')->once()->andReturn('What a wrap-up, party people!');
    app()->instance(AIProvider::class, $provider);

    // Completing the only turn of a 1-round game fires RoundCompleted and
    // GameCompleted together (see GameSessionService::advance()); faking
    // RoundCompleted keeps this test isolated to the GameCompleted listener
    // rather than also running the new SendAiHostRoundMessage listener.
    Event::fake([AiHostMessageSent::class, RoundCompleted::class]);

    $service->nextTurn($session);

    Event::assertDispatched(AiHostMessageSent::class, fn ($event) => $event->gameSessionId === $session->id && $event->message === 'What a wrap-up, party people!');
});

it('does not broadcast when the AI provider fails, and lets the failure surface for retry', function () {
    [$host, $party] = createLiveSoloGameSessionForAiHost();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    $provider = Mockery::mock(AIProvider::class);
    $provider->shouldReceive('respond')->once()->andThrow(new RuntimeException('OpenAI is down'));
    app()->instance(AIProvider::class, $provider);

    Event::fake([AiHostMessageSent::class, RoundCompleted::class]);

    // On the `sync` queue driver used in tests, a listener that throws has
    // no separate worker to retry it later — the exception surfaces
    // immediately to the caller (after SyncQueue::handleException() has
    // already invoked failed()). With a real queue connection, tries()/
    // backoff() below govern the retry instead, and the completing
    // player's request is never affected either way, since dispatching a
    // queued listener doesn't wait for it to run.
    expect(fn () => $service->nextTurn($session))->toThrow(RuntimeException::class, 'OpenAI is down');

    Event::assertNotDispatched(AiHostMessageSent::class);
});

it('retries up to 4 times with 5s/15s/30s backoff before giving up', function () {
    $listener = app(SendAiHostMessage::class);

    expect($listener->tries())->toBe(4);
    expect($listener->backoff())->toBe([5, 15, 30]);
});
