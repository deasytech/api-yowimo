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
use App\Services\AI\AiProviderFailedException;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
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
    finishGameVotingWindow($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendAiHostMessage::class);
});

it('broadcasts an AI host message into the game session channel when GameCompleted fires', function () {
    [$host, $party] = createLiveSoloGameSessionForAiHost();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    $provider = Mockery::mock(AIProvider::class);
    $provider->shouldReceive('respond')->once()->andReturn('What a wrap-up, party people!');
    app()->instance(AIProvider::class, $provider);

    // Completing the only turn of a 1-round game fires RoundCompleted, then
    // GameCompleted once the final voting window closes (see
    // GameSessionService::advance()/finishVoting()); faking RoundCompleted
    // keeps this test isolated to the GameCompleted listener rather than
    // also running the SendAiHostRoundMessage listener.
    Event::fake([AiHostMessageSent::class, RoundCompleted::class]);

    $service->nextTurn($session);
    finishGameVotingWindow($session);

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
    $service->nextTurn($session);

    expect(fn () => finishGameVotingWindow($session))->toThrow(RuntimeException::class, 'OpenAI is down');

    Event::assertNotDispatched(AiHostMessageSent::class);
});

it('skips the message with a warning instead of retrying a terminal provider failure', function () {
    [$host, $party] = createLiveSoloGameSessionForAiHost();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    $provider = Mockery::mock(AIProvider::class);
    $provider->shouldReceive('respond')->once()->andThrow(
        new AiProviderFailedException('OpenAI rejected the request: You have no credits remaining.', retryable: false),
    );
    app()->instance(AIProvider::class, $provider);

    Event::fake([AiHostMessageSent::class, RoundCompleted::class]);
    $log = Log::spy();

    $service->nextTurn($session);

    // Terminal failures (no credits, bad key, rejected request) repeat
    // identically on every attempt, so the listener skips the message rather
    // than throwing the exception at the retrying queue worker — completing
    // the game still succeeds.
    finishGameVotingWindow($session);

    Event::assertNotDispatched(AiHostMessageSent::class);
    $log->shouldHaveReceived('warning')
        ->once()
        ->withArgs(fn (string $message, array $context) => $context['game_session_id'] === $session->id);
});

it('retries up to 4 times with 5s/15s/30s backoff before giving up', function () {
    $listener = app(SendAiHostMessage::class);

    expect($listener->tries())->toBe(4);
    expect($listener->backoff())->toBe([5, 15, 30]);
});
