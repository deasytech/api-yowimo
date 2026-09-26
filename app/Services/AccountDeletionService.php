<?php

namespace App\Services;

use App\Enums\PartyMemberStatus;
use App\Enums\PartyStatus;
use App\Enums\UserStatus;
use App\Exceptions\Api\AccountDeletionFailedException;
use App\Models\Party;
use App\Models\PartyMember;
use App\Models\User;
use App\Services\Clerk\ClerkBackendClient;
use App\Services\Friends\FriendshipService;
use App\Services\Notifications\PushTokenService;
use App\Services\Parties\PartyMembershipService;
use Illuminate\Support\Facades\DB;

class AccountDeletionService
{
    public function __construct(
        private readonly ClerkBackendClient $clerk,
        private readonly PartyMembershipService $memberships,
        private readonly FriendshipService $friendships,
        private readonly PushTokenService $pushTokens,
    ) {}

    /**
     * Soft-delete the user's account.
     *
     * The Clerk user is deleted first, so a Clerk failure leaves everything
     * untouched and the request can simply be retried. Once Clerk has
     * succeeded, the local cleanup runs via deleteLocally(). If that cleanup
     * fails, it is still completed by a retry of this request (Clerk's 404
     * for an already-deleted user counts as success) or by Clerk's own
     * `user.deleted` webhook, which runs the same deleteLocally().
     *
     * @throws AccountDeletionFailedException if the Clerk user couldn't be deleted.
     */
    public function delete(User $user): void
    {
        $this->clerk->deleteUser($user->clerk_user_id);

        $this->deleteLocally($user);
    }

    /**
     * Local-only account cleanup, without calling Clerk. Idempotent: every
     * step only touches rows that are still open, and an existing
     * `deleted_at` is kept, so it is safe to run again after a partial
     * failure or when the `user.deleted` webhook arrives for a user already
     * deleted through the API.
     *
     * Wallet, ledger, purchase, and gameplay history rows are kept, per the
     * "never soft delete" policy for financial records.
     */
    public function deleteLocally(User $user): void
    {
        DB::transaction(function () use ($user) {
            $this->closeHostedParties($user);
            $this->leaveJoinedParties($user);
            $this->friendships->closeAll($user);
            $this->pushTokens->unregister($user);

            $user->forceFill([
                'status' => UserStatus::Deactivated,
                'deleted_at' => $user->deleted_at ?? now(),
            ])->save();
        });
    }

    /**
     * Parties that haven't started are cancelled; live ones are ended.
     * Already ended/cancelled parties are left as they are.
     */
    private function closeHostedParties(User $user): void
    {
        Party::query()
            ->where('host_id', $user->id)
            ->whereIn('status', [PartyStatus::Draft, PartyStatus::Scheduled, PartyStatus::Live])
            ->get()
            ->each(fn (Party $party) => $party->status === PartyStatus::Live
                ? $this->memberships->end($party)
                : $this->memberships->cancel($party));
    }

    private function leaveJoinedParties(User $user): void
    {
        PartyMember::query()
            ->where('user_id', $user->id)
            ->where('status', PartyMemberStatus::Active)
            ->whereHas('party', fn ($query) => $query->where('host_id', '!=', $user->id))
            ->with('party')
            ->get()
            ->each(fn (PartyMember $membership) => $this->memberships->leave($user, $membership->party));
    }
}
