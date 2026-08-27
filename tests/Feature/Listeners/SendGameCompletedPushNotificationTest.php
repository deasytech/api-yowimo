<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Listeners\SendGameCompletedPushNotification;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Notifications\GameCompletedNotification;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

function createLiveGameSessionForGameCompletedNotification(): array
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

    return [$host, $party];
}

it('pushes the game-completed notification listener onto the queue when GameCompleted fires', function () {
    [$host, $party] = createLiveGameSessionForGameCompletedNotification();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Queue::fake();

    $service->nextTurn($session);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendGameCompletedPushNotification::class);
});

it('notifies every party member when the game completes', function () {
    [$host, $party] = createLiveGameSessionForGameCompletedNotification();

    $service = app(GameSessionService::class);
    $session = $service->start($host, $party, 1);

    Notification::fake();

    $session = $service->nextTurn($session);

    Notification::assertSentTo($host, GameCompletedNotification::class, fn ($notification) => $notification->gameSession->id === $session->id);
});
