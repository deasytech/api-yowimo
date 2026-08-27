<?php

use App\Enums\PartyStatus;
use App\Listeners\SendPartyMemberLeftPushNotification;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Notifications\PartyMemberLeftNotification;
use App\Services\Parties\PartyMembershipService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('pushes the party-member-left notification listener onto the queue when PartyMemberLeft fires', function () {
    Queue::fake();

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'max_players' => 8, 'players_count' => 2]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    $member = User::factory()->create();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $member->id]);

    app(PartyMembershipService::class)->leave($member, $party);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendPartyMemberLeftPushNotification::class);
});

it('notifies the party host when another member leaves', function () {
    Notification::fake();

    $host = User::factory()->create();
    $party = Party::factory()->create(['host_id' => $host->id, 'status' => PartyStatus::Live, 'max_players' => 8, 'players_count' => 2]);
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $host->id]);
    $member = User::factory()->create();
    PartyMember::factory()->create(['party_id' => $party->id, 'user_id' => $member->id]);

    app(PartyMembershipService::class)->leave($member, $party);

    Notification::assertSentTo($host, PartyMemberLeftNotification::class, fn ($notification) => $notification->party->id === $party->id && $notification->leavingUser->id === $member->id);
    Notification::assertNothingSentTo($member);
});
