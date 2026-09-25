<?php

namespace App\Services\Parties;

use App\Enums\PartyMemberStatus;
use App\Enums\PartyStatus;
use App\Events\PartyMemberJoined;
use App\Events\PartyMemberLeft;
use App\Events\PartyStarted;
use App\Exceptions\Api\InvalidPartyTransitionException;
use App\Exceptions\Api\PartyFullException;
use App\Exceptions\Api\PartyHostCannotLeaveException;
use App\Exceptions\Api\PartyNotJoinableException;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PartyMembershipService
{
    /**
     * Statuses a party can be joined in. Also used by PartyService's room
     * code lookup, so a code for a draft/ended/cancelled party 404s the
     * same way an unrecognized one does, rather than resolving to a party
     * the caller can't actually join anyway.
     *
     * @var array<int, PartyStatus>
     */
    public const JOINABLE_STATUSES = [PartyStatus::Scheduled, PartyStatus::Live];

    /**
     * A rejoin (having previously left) reuses the same row rather than
     * inserting a new one — party_members has a unique (party_id, user_id)
     * constraint precisely so membership history survives as one row per
     * person per party, not one per join/leave cycle.
     *
     * @throws PartyNotJoinableException if the party's current status doesn't allow joining.
     * @throws PartyFullException if the party is already at capacity.
     */
    public function join(User $user, Party $party): Party
    {
        DB::transaction(function () use ($user, $party) {
            $party = Party::query()->whereKey($party->id)->lockForUpdate()->firstOrFail();

            $membership = PartyMember::query()->where('party_id', $party->id)->where('user_id', $user->id)->first();

            if ($membership && $membership->status === PartyMemberStatus::Active) {
                return;
            }

            if (! in_array($party->status, self::JOINABLE_STATUSES, true)) {
                throw new PartyNotJoinableException;
            }

            if ($party->players_count >= $party->max_players) {
                throw new PartyFullException;
            }

            if ($membership) {
                $membership->update([
                    'status' => PartyMemberStatus::Active,
                    'joined_at' => now(),
                    'left_at' => null,
                ]);
            } else {
                PartyMember::create([
                    'party_id' => $party->id,
                    'user_id' => $user->id,
                    'status' => PartyMemberStatus::Active,
                    'joined_at' => now(),
                ]);
            }

            $party->increment('players_count');

            PartyMemberJoined::dispatch($party->id, $user->id);
        });

        return $party->refresh();
    }

    /**
     * Marks the membership as left rather than deleting it, so history
     * (joined_at/left_at) survives for the joined-parties list.
     *
     * @throws PartyHostCannotLeaveException if the host tries to leave their own party.
     */
    public function leave(User $user, Party $party): Party
    {
        if ($party->host_id === $user->id) {
            throw new PartyHostCannotLeaveException;
        }

        DB::transaction(function () use ($user, $party) {
            $membership = PartyMember::query()
                ->where('party_id', $party->id)
                ->where('user_id', $user->id)
                ->where('status', PartyMemberStatus::Active)
                ->first();

            if (! $membership) {
                return;
            }

            $membership->update(['status' => PartyMemberStatus::Left, 'left_at' => now()]);

            if ($party->players_count > 0) {
                $party->decrement('players_count');
            }

            PartyMemberLeft::dispatch($party->id, $user->id);
        });

        return $party->refresh();
    }

    /**
     * @throws InvalidPartyTransitionException if the party isn't in a cancellable status.
     */
    public function cancel(Party $party): Party
    {
        if (! in_array($party->status, [PartyStatus::Draft, PartyStatus::Scheduled], true)) {
            throw new InvalidPartyTransitionException('This party cannot be cancelled from its current status.');
        }

        $party->update(['status' => PartyStatus::Cancelled]);

        return $party->refresh();
    }

    /**
     * @throws InvalidPartyTransitionException if the party isn't in a startable status.
     */
    public function start(Party $party): Party
    {
        if (! in_array($party->status, [PartyStatus::Draft, PartyStatus::Scheduled], true)) {
            throw new InvalidPartyTransitionException('This party cannot be started from its current status.');
        }

        $party->update(['status' => PartyStatus::Live]);

        PartyStarted::dispatch($party->id);

        return $party->refresh();
    }

    /**
     * @throws InvalidPartyTransitionException if the party isn't live.
     */
    public function end(Party $party): Party
    {
        if ($party->status !== PartyStatus::Live) {
            throw new InvalidPartyTransitionException('This party cannot be ended from its current status.');
        }

        $party->update(['status' => PartyStatus::Ended]);

        return $party->refresh();
    }
}
