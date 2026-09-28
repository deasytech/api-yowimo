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
use App\Services\Paystack\PaystackClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AccountDeletionService
{
    public function __construct(
        private readonly ClerkBackendClient $clerk,
        private readonly PartyMembershipService $memberships,
        private readonly FriendshipService $friendships,
        private readonly PushTokenService $pushTokens,
        private readonly PaystackClient $paystack,
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
     * "never soft delete" policy for financial records. Saved payment
     * methods are the one exception — a reusable charge credential is
     * purged, not kept, since nothing should be able to charge a deleted
     * account's card again.
     */
    public function deleteLocally(User $user): void
    {
        $this->purgePaymentMethods($user);

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
     * Deactivates each saved Paystack authorization remotely (best-effort —
     * a Paystack failure is logged, not thrown, so it never blocks account
     * deletion), then removes the local payment_methods row regardless of
     * whether the remote call succeeded, so a saved card is never left
     * reachable for charging after the account is gone.
     */
    private function purgePaymentMethods(User $user): void
    {
        foreach ($user->paymentMethods as $method) {
            if ($method->provider === 'paystack') {
                try {
                    $response = $this->paystack->deactivateAuthorization($method->authorization_code);

                    if (($response['status'] ?? false) !== true) {
                        // A completed-but-declined request (e.g. an already
                        // revoked or unrecognized code) — PaystackClient
                        // never throws for these, so they'd otherwise pass
                        // silently. The authorization_code is logged (not
                        // just the soon-to-be-deleted payment_method_id) so
                        // it can still be deactivated manually via Paystack
                        // after this row is gone.
                        Log::warning('Paystack declined to deactivate an authorization during account deletion.', [
                            'user_id' => $user->id,
                            'payment_method_id' => $method->id,
                            'authorization_code' => $method->authorization_code,
                            'response' => $response,
                        ]);
                    }
                } catch (Throwable $e) {
                    Log::warning('Failed to deactivate Paystack authorization during account deletion.', [
                        'user_id' => $user->id,
                        'payment_method_id' => $method->id,
                        'authorization_code' => $method->authorization_code,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $method->delete();
        }
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
