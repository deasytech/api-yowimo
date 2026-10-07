<?php

namespace App\Services\Referrals;

use App\Enums\WalletTransactionType;
use App\Exceptions\Api\InvalidReferralCodeException;
use App\Exceptions\Api\ReferralAlreadyClaimedException;
use App\Exceptions\Api\SelfReferralException;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    public function __construct(private readonly WalletService $wallets) {}

    /**
     * Sets the caller's referred_by_user_id exactly once. A retry of the
     * same (or any) claim after it's already set just hits
     * ReferralAlreadyClaimedException harmlessly — no idempotency key needed.
     *
     * @throws ReferralAlreadyClaimedException if the caller already claimed a code.
     * @throws InvalidReferralCodeException if the code doesn't resolve to a user.
     * @throws SelfReferralException if the code resolves to the caller themself.
     */
    public function claim(User $user, string $code): void
    {
        DB::transaction(function () use ($user, $code) {
            $user = User::whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($user->referred_by_user_id !== null) {
                throw new ReferralAlreadyClaimedException;
            }

            $referrer = User::where('referral_code', strtoupper($code))->first();

            if (! $referrer) {
                throw new InvalidReferralCodeException;
            }

            if ($referrer->id === $user->id) {
                throw new SelfReferralException;
            }

            $user->update(['referred_by_user_id' => $referrer->id]);
        });
    }

    /**
     * @return array{referral_code: string, referred_count: int, tokens_earned: int, reward_amount: int}
     */
    public function summary(User $user): array
    {
        $wallet = $this->wallets->walletFor($user);

        $tokensEarned = (int) WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', WalletTransactionType::Referral)
            ->sum('amount');

        return [
            'referral_code' => $user->referral_code,
            'referred_count' => User::where('referred_by_user_id', $user->id)->count(),
            'tokens_earned' => $tokensEarned,
            'reward_amount' => $this->rewardAmount(),
        ];
    }

    public function rewardAmount(): int
    {
        return (int) config('services.referrals.reward_amount', 20);
    }

    /**
     * Pays out the flat reward to both the referrer and the referred user,
     * exactly once per referred user — called only once the referred user
     * has completed their first game (see GrantReferralReward). A no-op if
     * the user wasn't referred, or the referrer no longer exists.
     */
    public function payoutFor(User $referredUser): void
    {
        if (! $referredUser->referred_by_user_id) {
            return;
        }

        $referrer = User::find($referredUser->referred_by_user_id);

        if (! $referrer) {
            return;
        }

        $amount = $this->rewardAmount();
        $idempotencyKey = "referral-reward-{$referredUser->id}";

        $this->wallets->credit(
            $referrer,
            $amount,
            WalletTransactionType::Referral,
            reference: $referredUser,
            description: "Referral reward for inviting {$referredUser->username}",
            idempotencyKey: $idempotencyKey,
        );

        $this->wallets->credit(
            $referredUser,
            $amount,
            WalletTransactionType::Referral,
            reference: $referrer,
            description: 'Referral reward for joining via invite',
            idempotencyKey: $idempotencyKey,
        );
    }
}
