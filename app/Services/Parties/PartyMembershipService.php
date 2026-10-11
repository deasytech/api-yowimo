<?php

namespace App\Services\Parties;

use App\Enums\JoinMode;
use App\Enums\PartyMemberStatus;
use App\Enums\PartyStatus;
use App\Enums\WalletTransactionType;
use App\Events\PartyMemberJoined;
use App\Events\PartyMemberLeft;
use App\Events\PartyMemberReady;
use App\Events\PartyMemberUnready;
use App\Events\PartyStarted;
use App\Exceptions\Api\InsufficientWalletBalanceException;
use App\Exceptions\Api\InvalidPartyTransitionException;
use App\Exceptions\Api\PartyFullException;
use App\Exceptions\Api\PartyHostCannotLeaveException;
use App\Exceptions\Api\PartyNotJoinableException;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Services\Game\GameSessionService;
use App\Services\Sponsorship\SponsorshipService;
use App\Services\Wallet\WalletService;
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

    public function __construct(
        private readonly GameSessionService $games,
        private readonly WalletService $wallets,
        private readonly SponsorshipService $sponsorships,
    ) {}

    /**
     * A rejoin (having previously left) reuses the same row rather than
     * inserting a new one — party_members has a unique (party_id, user_id)
     * constraint precisely so membership history survives as one row per
     * person per party, not one per join/leave cycle.
     *
     * @throws PartyNotJoinableException if the party's current status doesn't allow joining.
     * @throws PartyFullException if the party is already at capacity.
     * @throws InsufficientWalletBalanceException if the entry fee applies and the guest can't afford it,
     *                                            or (for a free, unsponsored party) if the host can't afford to cover this guest.
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

            // A rejoin never re-charges: the membership row already existing
            // (Active handled above, so this is always a prior Left row) is
            // itself the proof the entry fee was already paid on first join.
            if (! $membership) {
                $this->chargeEntryFee($user, $party);
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
     * Skipped entirely for a full_party-sponsored party once its sponsor has
     * paid — the sponsor already covers every slot. Otherwise charges the
     * guest the entry fee. For a free, sponsorship-opted-in party
     * (entry_fee = 0 with a sponsorship_scope, i.e. creation_fee — a
     * full_party one is never joinable until its sponsor has already paid,
     * see above) there's nothing to charge the guest, so the host is
     * charged the per-guest cost instead (same rate the full_party formula
     * uses for a free party — see SponsorshipService::freePartyGuestCost()),
     * since nobody else is paying for this guest's slot. An ordinary free
     * party with no sponsorship_scope at all was never opted into this and
     * stays free for everyone, host included.
     */
    private function chargeEntryFee(User $user, Party $party): void
    {
        if ($this->sponsorships->isFullyCoveredByPaidSponsor($party)) {
            return;
        }

        if ($party->entry_fee > 0) {
            $this->wallets->debit(
                $user,
                $party->entry_fee,
                WalletTransactionType::PartyEntry,
                reference: $party,
                description: "Party entry: {$party->title}",
                idempotencyKey: "party-entry-{$party->id}-{$user->id}",
            );

            return;
        }

        $guestCost = $party->sponsorship_scope !== null ? $this->sponsorships->freePartyGuestCost() : 0;

        if ($guestCost <= 0) {
            return;
        }

        $this->wallets->debit(
            $party->host,
            $guestCost,
            WalletTransactionType::FreePartyGuestCost,
            reference: $party,
            description: "Free party guest cost: {$party->title}",
            idempotencyKey: "party-entry-host-{$party->id}-{$user->id}",
        );
    }

    /**
     * Everyone who has been in the party — current members and those who
     * left — in join order, with their user loaded. Members whose account
     * has been deleted are left out; guest (pass-and-play) members have no
     * account to begin with and are always included.
     *
     * @return Collection<int, PartyMember>
     */
    public function players(Party $party): Collection
    {
        return PartyMember::query()
            ->where('party_id', $party->id)
            ->where(fn ($query) => $query->whereHas('user')->orWhereNull('user_id'))
            ->with('user')
            ->orderBy('joined_at')
            ->orderBy('id')
            ->get()
            ->each(fn (PartyMember $member) => $member->setRelation('party', $party));
    }

    /**
     * Adds an in-room guest (pass-and-play, no account) to the party. Only
     * the host can do this — they're physically present and vouching for
     * who's in the room — so unlike join(), there's no rejoin-reuse case: a
     * new guest is always a new row.
     *
     * @param  array{guest_name: string, guest_emoji: ?string, join_mode: JoinMode}  $data
     *
     * @throws PartyNotJoinableException if the party's current status doesn't allow joining.
     * @throws PartyFullException if the party is already at capacity.
     */
    public function addGuest(Party $party, array $data): PartyMember
    {
        return DB::transaction(function () use ($party, $data) {
            $party = Party::query()->whereKey($party->id)->lockForUpdate()->firstOrFail();

            if (! in_array($party->status, self::JOINABLE_STATUSES, true)) {
                throw new PartyNotJoinableException;
            }

            if ($party->players_count >= $party->max_players) {
                throw new PartyFullException;
            }

            $member = PartyMember::create([
                'party_id' => $party->id,
                'guest_name' => $data['guest_name'],
                'guest_emoji' => $data['guest_emoji'] ?? null,
                'join_mode' => $data['join_mode'],
                'status' => PartyMemberStatus::Active,
                'is_ready' => false,
                'joined_at' => now(),
            ]);

            $party->increment('players_count');

            return $member;
        });
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
        if (! in_array($party->status, [PartyStatus::Draft, PartyStatus::PendingSponsorship, PartyStatus::Scheduled], true)) {
            throw new InvalidPartyTransitionException('This party cannot be cancelled from its current status.');
        }

        DB::transaction(function () use ($party) {
            $party->update(['status' => PartyStatus::Cancelled]);

            $this->sponsorships->refundForCancelledParty($party);
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

        DB::transaction(function () use ($party) {
            $party->update(['status' => PartyStatus::Ended]);

            $this->games->endForParty($party);

            $this->sponsorships->refundUnusedFullPartySponsorship($party);
        });

        return $party->refresh();
    }
}
