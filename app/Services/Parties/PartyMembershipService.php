<?php

namespace App\Services\Parties;

use App\Enums\PartyMemberStatus;
use App\Enums\PartyStatus;
use App\Events\PartyMemberJoined;
use App\Events\PartyMemberLeft;
use App\Events\PartyMemberReady;
use App\Events\PartyMemberUnready;
use App\Events\PartyStarted;
use App\Exceptions\Api\InvalidPartyTransitionException;
use App\Exceptions\Api\PartyFullException;
use App\Exceptions\Api\PartyHostCannotLeaveException;
use App\Exceptions\Api\PartyNotJoinableException;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Services\Game\GameSessionService;
use Illuminate\Database\Eloquent\Collection;
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

    public function __construct(private readonly GameSessionService $games) {}

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
                    'is_ready' => false,
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

            $this->games->handlePlayerJoined($party, $user->id);

            PartyMemberJoined::dispatch($party->id, $user->id);
        });

        return $party->refresh();
    }

    /**
     * Everyone who has been in the party — current members and those who
     * left — in join order, with their user loaded. Members whose account
     * has been deleted are left out.
     *
     * @return Collection<int, PartyMember>
     */
    public function players(Party $party): Collection
    {
        return PartyMember::query()
            ->where('party_id', $party->id)
            ->whereHas('user')
            ->with('user')
            ->orderBy('joined_at')
            ->orderBy('id')
            ->get()
            ->each(fn (PartyMember $member) => $member->setRelation('party', $party));
    }

    /**
     * Informational only: toggling ready never gates whether the host can
     * start the party (see PartyMembershipService::start()) — it's a signal
     * for the lobby UI, not a lifecycle rule.
     */
    public function ready(User $user, Party $party): PartyMember
    {
        $membership = $this->activeMembership($user, $party);

        if (! $membership->is_ready) {
            $membership->update(['is_ready' => true]);
            PartyMemberReady::dispatch($party->id, $user->id);
        }

        return $membership->setRelation('party', $party);
    }

    public function unready(User $user, Party $party): PartyMember
    {
        $membership = $this->activeMembership($user, $party);

        if ($membership->is_ready) {
            $membership->update(['is_ready' => false]);
            PartyMemberUnready::dispatch($party->id, $user->id);
        }

        return $membership->setRelation('party', $party);
    }

    private function activeMembership(User $user, Party $party): PartyMember
    {
        return PartyMember::query()
            ->where('party_id', $party->id)
            ->where('user_id', $user->id)
            ->where('status', PartyMemberStatus::Active)
            ->firstOrFail();
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
            $party = Party::query()->whereKey($party->id)->lockForUpdate()->firstOrFail();

            $membership = PartyMember::query()
                ->where('party_id', $party->id)
                ->where('user_id', $user->id)
                ->where('status', PartyMemberStatus::Active)
                ->first();

            if (! $membership) {
                return;
            }

            $membership->update(['status' => PartyMemberStatus::Left, 'is_ready' => false, 'left_at' => now()]);

            if ($party->players_count > 0) {
                $party->decrement('players_count');
            }

            $this->games->handlePlayerLeft($party, $user->id);

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

        DB::transaction(function () use ($party) {
            $party->update(['status' => PartyStatus::Ended]);

            $this->games->endForParty($party);
        });

        return $party->refresh();
    }
}
