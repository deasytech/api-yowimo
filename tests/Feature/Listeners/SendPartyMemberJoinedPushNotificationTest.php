<?php

use App\Enums\PartyStatus;
use App\Listeners\SendPartyMemberJoinedPushNotification;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Notifications\PartyMemberJoinedNotification;
use App\Services\Parties\PartyMembershipService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('pushes the party-member-joined notification listener onto the queue when PartyMemberJoined fires', function () {
    Queue::fake();

    $party = Party::factory()->create(['status' => PartyStatus::Live, 'max_players' => 8, 'players_count' => 1]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $party->host_id]);
    $joiner = User::factory()->create();

    app(PartyMembershipService::class)->join($joiner, $party);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendPartyMemberJoinedPushNotification::class);
});

it('notifies the party host when another user joins', function () {
    Notification::fake();

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'max_players' => 8, 'players_count' => 1]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    $joiner = User::factory()->create();

    app(PartyMembershipService::class)->join($joiner, $party);

    Notification::assertSentTo($host, PartyMemberJoinedNotification::class, fn ($notification) => $notification->party->id === $party->id && $notification->joiningUser->id === $joiner->id);
    Notification::assertNothingSentTo($joiner);
});
