<?php

namespace App\Services\Sponsorship;

use App\Enums\PartyStatus;
use App\Enums\SponsorshipInviteStatus;
use App\Enums\SponsorshipScope;
use App\Enums\WalletTransactionType;
use App\Events\PartyStarted;
use App\Exceptions\Api\HostCannotSponsorOwnPartyException;
use App\Exceptions\Api\SponsorshipAmountZeroException;
use App\Exceptions\Api\SponsorshipInviteNotPendingException;
use App\Exceptions\Api\SponsorshipScopeMismatchException;
use App\Models\Party;
use App\Models\SponsorshipInvite;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SponsorshipService
{
    public function __construct(private readonly WalletService $wallets) {}

    /**
     * Creates a sponsorship invite link for the party's chosen scope, or
     * returns the existing pending (non-expired) one for that scope instead
     * of creating a duplicate. Locks the party row for the duration, so two
     * concurrent calls for the same party/scope can't both miss the
     * dedup check above and each insert their own payable invite.
     *
     * @throws SponsorshipScopeMismatchException if the party wasn't created with this scope.
     * @throws SponsorshipAmountZeroException if the computed amount is zero.
     */
    public function createInvite(Party $party, SponsorshipScope $scope): SponsorshipInvite
    {
        if ($party->sponsorship_scope !== $scope) {
            throw new SponsorshipScopeMismatchException;
        }

        return DB::transaction(function () use ($party, $scope) {
            Party::query()->whereKey($party->id)->lockForUpdate()->firstOrFail();

            $existing = SponsorshipInvite::query()
                ->where('party_id', $party->id)
                ->where('scope', $scope)
                ->where('status', SponsorshipInviteStatus::Pending)
                ->where('expires_at', '>', now())
                ->first();

            if ($existing) {
                return $existing->setRelation('party', $party);
            }

            $amount = $this->computeAmount($party, $scope);

            if ($amount <= 0) {
                throw new SponsorshipAmountZeroException;
            }

            $invite = SponsorshipInvite::create([
                'party_id' => $party->id,
                'scope' => $scope,
                'amount' => $amount,
                'status' => SponsorshipInviteStatus::Pending,
                'token' => (string) Str::ulid(),
                'expires_at' => now()->addHours((int) config('services.sponsorship.invite_ttl_hours', 72)),
            ]);

            return $invite->setRelation('party', $party);
        });
    }

    /**
     * creation_fee is just the game type's cost; full_party additionally
     * covers every other reservable slot (max_players - 1, the host's own
     * slot excluded) at the party's entry fee, decided up front from the
     * party's fixed capacity rather than how many guests actually join.
     */
    public function computeAmount(Party $party, SponsorshipScope $scope): int
    {
        $creationFee = $party->gameType?->cost ?? 0;

        if ($scope === SponsorshipScope::CreationFee) {
            return $creationFee;
        }

        return $creationFee + $party->entry_fee * max($party->max_players - 1, 0);
    }

    public function findByToken(string $token): SponsorshipInvite
    {
        return SponsorshipInvite::query()
            ->with(['party.host', 'sponsor'])
            ->where('token', $token)
            ->firstOrFail();
    }

    /**
     * Debits the sponsor's wallet for the invite's amount and, if the party
     * was waiting on this exact sponsorship, moves it into the status it
     * would have gotten at creation had sponsorship not been required
     * (Scheduled or Live, depending on starts_at).
     *
     * @throws HostCannotSponsorOwnPartyException if the caller hosts the party being sponsored.
     * @throws SponsorshipInviteNotPendingException if the invite is already paid, cancelled, or expired.
     */
    public function pay(User $sponsor, SponsorshipInvite $invite, ?string $idempotencyKey): SponsorshipInvite
    {
        return DB::transaction(function () use ($sponsor, $invite, $idempotencyKey) {
            $invite = SponsorshipInvite::query()->whereKey($invite->id)->lockForUpdate()->firstOrFail();
            $party = Party::query()->whereKey($invite->party_id)->lockForUpdate()->firstOrFail();

            if ($party->host_id === $sponsor->id) {
                throw new HostCannotSponsorOwnPartyException;
            }

            if ($invite->effectiveStatus() !== SponsorshipInviteStatus::Pending) {
                throw new SponsorshipInviteNotPendingException;
            }

            // The party itself, not just this invite, must still be waiting
            // on this exact sponsorship — e.g. a second pending invite for
            // the same scope (created before the party activated, or before
            // the lock in createInvite() existed) can't be paid once the
            // party's already been activated, cancelled, or ended by then.
            if ($party->status !== PartyStatus::PendingSponsorship) {
                throw new SponsorshipInviteNotPendingException;
            }

            $this->wallets->debit(
                $sponsor,
                $invite->amount,
                WalletTransactionType::Sponsor,
                reference: $party,
                description: "Sponsorship: {$party->title}",
                idempotencyKey: $idempotencyKey,
            );

            $invite->update([
                'sponsor_id' => $sponsor->id,
                'status' => SponsorshipInviteStatus::Paid,
                'paid_at' => now(),
            ]);

            if ($party->status === PartyStatus::PendingSponsorship && $party->sponsorship_scope === $invite->scope) {
                $this->activateParty($party);
            }

            return $invite->setRelation('party', $party)->setRelation('sponsor', $sponsor);
        });
    }

    private function activateParty(Party $party): void
    {
        $newStatus = $party->starts_at !== null ? PartyStatus::Scheduled : PartyStatus::Live;

        $party->update(['status' => $newStatus]);

        if ($newStatus === PartyStatus::Live) {
            PartyStarted::dispatch($party->id);
        }
    }

    /**
     * Called when a party is cancelled before it ran: refunds every guest
     * who actually paid an entry fee for it, and refunds (and closes out)
     * any sponsorship invite already paid for it — nothing happened, so
     * nothing charged against it should stick. The host's own game-type
     * creation fee, an unrelated and pre-existing charge, is untouched —
     * it's recorded under the same party_entry type/reference as a guest's
     * join charge, so it has to be excluded explicitly by wallet owner.
     */
    public function refundForCancelledParty(Party $party): void
    {
        $entryTransactions = WalletTransaction::query()
            ->where('type', WalletTransactionType::PartyEntry)
            ->where('reference_type', $party->getMorphClass())
            ->where('reference_id', $party->id)
            ->whereHas('wallet', fn ($query) => $query->where('user_id', '!=', $party->host_id))
            ->with('wallet.user')
            ->get();

        foreach ($entryTransactions as $transaction) {
            $user = $transaction->wallet->user;

            if (! $user) {
                continue;
            }

            $this->wallets->credit(
                $user,
                abs($transaction->amount),
                WalletTransactionType::Refund,
                reference: $party,
                description: "Party cancelled refund: {$party->title}",
                idempotencyKey: "party-entry-refund-{$transaction->id}",
            );
        }

        $paidInvites = SponsorshipInvite::query()
            ->where('party_id', $party->id)
            ->where('status', SponsorshipInviteStatus::Paid)
            ->with('sponsor')
            ->get();

        foreach ($paidInvites as $invite) {
            if (! $invite->sponsor) {
                continue;
            }

            $this->wallets->credit(
                $invite->sponsor,
                $invite->amount,
                WalletTransactionType::Refund,
                reference: $party,
                description: "Sponsorship cancelled refund: {$party->title}",
                idempotencyKey: "sponsorship-cancel-refund-{$invite->id}",
            );

            $invite->update(['status' => SponsorshipInviteStatus::Cancelled]);
        }
    }

    /**
     * Called when a party ends: a full_party sponsor paid up front for every
     * reservable guest slot (max_players - 1), whether or not it filled.
     * Refunds whatever fraction of that never got used.
     */
    public function refundUnusedFullPartySponsorship(Party $party): void
    {
        if ($party->sponsorship_scope !== SponsorshipScope::FullParty) {
            return;
        }

        $invite = SponsorshipInvite::query()
            ->where('party_id', $party->id)
            ->where('scope', SponsorshipScope::FullParty)
            ->where('status', SponsorshipInviteStatus::Paid)
            ->with('sponsor')
            ->first();

        if (! $invite || ! $invite->sponsor) {
            return;
        }

        $unfilledSlots = max($party->max_players - $party->players_count, 0);
        $refundAmount = $unfilledSlots * $party->entry_fee;

        if ($refundAmount <= 0) {
            return;
        }

        $this->wallets->credit(
            $invite->sponsor,
            $refundAmount,
            WalletTransactionType::Refund,
            reference: $party,
            description: "Unused sponsorship refund: {$party->title}",
            idempotencyKey: "sponsorship-unused-refund-{$invite->id}",
        );
    }

    /**
     * Whether a full_party sponsor has already paid for every guest slot —
     * if so, joining guests aren't charged their own entry fee.
     */
    public function isFullyCoveredByPaidSponsor(Party $party): bool
    {
        if ($party->sponsorship_scope !== SponsorshipScope::FullParty) {
            return false;
        }

        return SponsorshipInvite::query()
            ->where('party_id', $party->id)
            ->where('scope', SponsorshipScope::FullParty)
            ->where('status', SponsorshipInviteStatus::Paid)
            ->exists();
    }

    /**
     * @return array{players_covered: int, parties_count: int, tokens_spent: int}
     */
    public function summaryFor(User $sponsor): array
    {
        $paidInvites = SponsorshipInvite::query()
            ->where('sponsor_id', $sponsor->id)
            ->where('status', SponsorshipInviteStatus::Paid)
            ->with('party')
            ->get();

        return [
            'players_covered' => $paidInvites->sum(fn (SponsorshipInvite $invite) => $this->coveredGuestCount($invite)),
            'parties_count' => $paidInvites->count(),
            'tokens_spent' => (int) $paidInvites->sum('amount'),
        ];
    }

    /**
     * @param  array{per_page?: int|null, cursor?: string|null}  $filters
     */
    public function listPaidBy(User $sponsor, array $filters): CursorPaginator
    {
        return SponsorshipInvite::query()
            ->where('sponsor_id', $sponsor->id)
            ->where('status', SponsorshipInviteStatus::Paid)
            ->with(['party.host'])
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->cursorPaginate(
                perPage: min($filters['per_page'] ?? 20, 50),
                cursor: $filters['cursor'] ?? null,
            );
    }

    /**
     * A full_party sponsorship covers every guest slot (max_players - 1,
     * the host's own slot excluded); a creation_fee sponsorship covers no
     * player slots at all.
     */
    public function coveredGuestCount(SponsorshipInvite $invite): int
    {
        if ($invite->scope !== SponsorshipScope::FullParty) {
            return 0;
        }

        return max($invite->party->max_players - 1, 0);
    }
}
