<?php

use App\Enums\PartyStatus;
use App\Listeners\SendPartyStartedPushNotification;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Notifications\PartyStartedNotification;
use App\Services\Parties\PartyMembershipService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('pushes the party-started notification listener onto the queue when PartyStarted fires', function () {
    Queue::fake();

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Scheduled]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    $member = User::factory()->create();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $member->id]);

    app(PartyMembershipService::class)->start($party);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendPartyStartedPushNotification::class);
});

it('notifies non-host party members when a party starts, but not the host', function () {
    Notification::fake();

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Scheduled]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    $member = User::factory()->create();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $member->id]);

    app(PartyMembershipService::class)->start($party);

    Notification::assertSentTo($member, PartyStartedNotification::class, fn ($notification) => $notification->party->id === $party->id);
    Notification::assertNothingSentTo($host);
});
