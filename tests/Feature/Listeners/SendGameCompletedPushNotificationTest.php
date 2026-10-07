<?php

use App\Listeners\SendGameCompletedPushNotification;
use App\Models\PartyMember;
use App\Models\User;
use App\Notifications\GameCompletedNotification;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Support\MakesLiveGameSessionParties;

uses(MakesLiveGameSessionParties::class);

function createLiveGameSessionForGameCompletedNotification(): array
{
    [$host, $party] = test()->makeLiveGameSessionParty(cardsPerKind: 5);

    return [$host, $party];
}

it('pushes the game-completed notification listener onto the queue when GameCompleted fires', function () {
    [$host, $party] = createLiveGameSessionForGameCompletedNotification();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Queue::fake();

    $service->nextTurn($session);
    finishGameVotingWindow($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendGameCompletedPushNotification::class);
});

it('notifies every party member when the game completes', function () {
    [$host, $party] = createLiveGameSessionForGameCompletedNotification();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Notification::fake();

    $session = $service->nextTurn($session);
    finishGameVotingWindow($session);

    Notification::assertSentTo($host, GameCompletedNotification::class, fn ($notification) => $notification->gameSession->id === $session->id);
});

it('does not notify a member who has since left the party', function () {
    [$host, $party] = createLiveGameSessionForGameCompletedNotification();
    $formerMember = User::factory()->create();
    PartyMember::factory()->left()->create(['party_id' => $party->id, 'user_id' => $formerMember->id]);

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Notification::fake();

    $service->nextTurn($session);
    finishGameVotingWindow($session);

    Notification::assertNothingSentTo($formerMember);
});
