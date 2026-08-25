<?php

use App\Enums\PackCardKind;
use App\Enums\PartyStatus;
use App\Listeners\SendRoundCompletedPushNotification;
use App\Models\Pack;
use App\Models\PackCard;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Notifications\RoundCompletedNotification;
use App\Services\Game\GameSessionService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

function createLiveGameSessionForRoundNotification(): array
{
    $pack = Pack::factory()->create();
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Truth]);
    PackCard::factory()->count(5)->create(['pack_id' => $pack->id, 'kind' => PackCardKind::Dare]);

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'pack_id' => $pack->id, 'status' => PartyStatus::Live]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);

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
