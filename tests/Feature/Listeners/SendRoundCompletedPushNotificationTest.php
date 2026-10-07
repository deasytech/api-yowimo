<?php

use App\Listeners\SendRoundCompletedPushNotification;
use App\Models\PartyMember;
use App\Models\User;
use App\Notifications\RoundCompletedNotification;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\MakesLiveGameSessionParties;

uses(MakesLiveGameSessionParties::class);

function createLiveGameSessionForRoundNotification(): array
{
    [$host, $party] = test()->makeLiveGameSessionParty(cardsPerKind: 5);

    return [$host, $party];
}

it('pushes the round-completed notification listener onto the queue when RoundCompleted fires', function () {
    [$host, $party] = createLiveGameSessionForRoundNotification();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 5);

    Queue::fake();

    $service->nextTurn($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendRoundCompletedPushNotification::class);
});

it('notifies every party member when a round completes', function () {
    [$host, $party] = createLiveGameSessionForRoundNotification();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 5);
    $round = $session->currentRound();

    Notification::fake();

    $service->nextTurn($session);

    Notification::assertSentTo($host, RoundCompletedNotification::class, fn ($notification) => $notification->round->id === $round->id && $notification->gameSession->id === $session->id);
});

it('does not notify a member who has since left the party', function () {
    [$host, $party] = createLiveGameSessionForRoundNotification();
    $formerMember = User::factory()->create();
    PartyMember::factory()->left()->create(['party_id' => $party->id, 'user_id' => $formerMember->id]);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 5);

    Notification::fake();

    $service->nextTurn($session);

    Notification::assertNothingSentTo($formerMember);
});
