<?php

namespace App\Services\Parties;

use App\Enums\PartyStatus;
use App\Events\PartyMemberJoined;
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
     * Statuses a party can be joined in.
     *
     * @var array<int, PartyStatus>
     */
    private const JOINABLE_STATUSES = [PartyStatus::Scheduled, PartyStatus::Live];

    /**
     * @throws PartyNotJoinableException if the party's current status doesn't allow joining.
     * @throws PartyFullException if the party is already at capacity.
     */
    public function join(User $user, Party $party): Party
    {
        DB::transaction(function () use ($user, $party) {
            $party = Party::query()->whereKey($party->id)->lockForUpdate()->firstOrFail();

            if (PartyMember::query()->where('party_id', $party->id)->where('user_id', $user->id)->exists()) {
                return;
            }

            if (! in_array($party->status, self::JOINABLE_STATUSES, true)) {
                throw new PartyNotJoinableException;
            }

            if ($party->players_count >= $party->max_players) {
                throw new PartyFullException;
            }

            PartyMember::create([
                'party_id' => $party->id,
                'user_id' => $user->id,
                'joined_at' => now(),
            ]);

            $party->increment('players_count');

            PartyMemberJoined::dispatch($party->id, $user->id);
        });

        return $party->refresh();
    }

    /**
     * @throws PartyHostCannotLeaveException if the host tries to leave their own party.
     */
    public function leave(User $user, Party $party): Party
    {
        if ($party->host_id === $user->id) {
            throw new PartyHostCannotLeaveException;
        }

        DB::transaction(function () use ($user, $party) {
            $deleted = PartyMember::query()
                ->where('party_id', $party->id)
                ->where('user_id', $user->id)
                ->delete();

            if ($deleted > 0 && $party->players_count > 0) {
                $party->decrement('players_count');
            }
        });

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
