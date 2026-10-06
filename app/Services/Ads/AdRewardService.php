<?php

namespace App\Services\Ads;

use App\Enums\AdRewardSessionStatus;
use App\Enums\WalletTransactionType;
use App\Exceptions\Api\AdRewardDailyCapReachedException;
use App\Exceptions\Api\AdRewardsDisabledException;
use App\Models\AdRewardSession;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdRewardService
{
    private const SESSION_TTL_MINUTES = 15;

    public function __construct(private readonly WalletService $wallets) {}

    /**
     * Mints a single-use, short-lived session the frontend attaches as the
     * ad's SSV customData. The cap/enabled checks here are advisory only
     * (UX: don't load an ad that can never pay out) — the real, race-proof
     * enforcement happens in verifyAndCredit() under the wallet row lock.
     *
     * @return array{model: AdRewardSession, plaintext_token: string}
     *
     * @throws AdRewardsDisabledException if the feature is currently disabled.
     * @throws AdRewardDailyCapReachedException if today's cap is already reached.
     */
    public function mintSession(User $user): array
    {
        if (! $this->enabled()) {
            throw new AdRewardsDisabledException;
        }

        if ($this->dailyProgress($user)['remaining'] <= 0) {
            throw new AdRewardDailyCapReachedException;
        }

        $plaintextToken = Str::random(64);

        $session = AdRewardSession::create([
            'user_id' => $user->id,
            'token_hash' => AdRewardSession::hashToken($plaintextToken),
            'status' => AdRewardSessionStatus::Pending,
            'reward_amount' => $this->tokensPerAd(),
            'expires_at' => now()->addMinutes(self::SESSION_TTL_MINUTES),
        ]);

        return ['model' => $session, 'plaintext_token' => $plaintextToken];
    }

    /**
     * @return array{watched_today: int, daily_cap: int, remaining: int, next_reset_at: Carbon, enabled: bool, tokens_per_ad: int, can_earn: bool, server_date: string}
     */
    public function dailyProgress(User $user): array
    {
        $watchedToday = $this->creditedToday($user->id)->count();
        $dailyLimit = $this->dailyLimit();
        $remaining = max($dailyLimit - $watchedToday, 0);
        $enabled = $this->enabled();

        return [
            'watched_today' => $watchedToday,
            'daily_cap' => $dailyLimit,
            'remaining' => $remaining,
            // The cap resets on the server's UTC day boundary — same
            // simplification as creditedToday()'s whereDate(), not the
            // user's local timezone.
            'next_reset_at' => today()->addDay(),
            'enabled' => $enabled,
            'tokens_per_ad' => $this->tokensPerAd(),
            // So the client never has to derive this itself from the other
            // fields — the backend stays the single source of truth for
            // whether tapping "watch an ad" would currently do anything.
            'can_earn' => $enabled && $remaining > 0,
            'server_date' => today()->toDateString(),
        ];
    }

    /**
     * Called only after the controller has verified the AdMob SSV signature
     * — this is where the daily cap is *actually* enforced, race-proof. An
     * unknown token, an already-resolved session, or an expired one is a
     * silent no-op: the caller (the webhook controller) always responds 200
     * once the signature itself checked out, so Google stops retrying.
     *
     * @param  array<string, mixed>  $ssvParams  The verified SSV callback's query params.
     */
    public function verifyAndCredit(array $ssvParams): void
    {
        $plaintextToken = $ssvParams['custom_data'] ?? null;

        if (! $plaintextToken) {
            return;
        }

        $tokenHash = AdRewardSession::hashToken($plaintextToken);

        DB::transaction(function () use ($tokenHash, $ssvParams) {
            $session = AdRewardSession::query()->where('token_hash', $tokenHash)->lockForUpdate()->first();

            if (! $session || $session->status !== AdRewardSessionStatus::Pending) {
                Log::warning('Rewarded ad SSV callback ignored: unknown or already-resolved session.', [
                    'transaction_id' => $ssvParams['transaction_id'] ?? null,
                ]);

                return;
            }

            if ($this->rejectExpiredOrDisabled($session)) {
                return;
            }

            // Locking the wallet row before re-counting today's total is what
            // makes the cap check atomic: a concurrent verifyAndCredit() for
            // the same user blocks here until this transaction commits, so
            // its own count read is never stale. This is the same row
            // WalletService::credit() below locks again internally — safe,
            // since a connection never blocks on a lock it already holds;
            // this outer lock does the serializing work, not the inner one.
            $wallet = $this->wallets->walletFor($session->user);
            Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($this->rejectOverCap($session)) {
                return;
            }

            $this->creditSession($session, $ssvParams);
        });
    }

    private function rejectExpiredOrDisabled(AdRewardSession $session): bool
    {
        if ($session->expires_at->isPast()) {
            $this->rejectSession($session, null, 'Rewarded ad SSV callback rejected: session expired.');

            return true;
        }

        if (! $this->enabled()) {
            $this->rejectSession($session, ['reason' => 'rewarded_ads_disabled'], 'Rewarded ad SSV callback rejected: rewarded ads are currently disabled.');

            return true;
        }

        return false;
    }

    private function rejectOverCap(AdRewardSession $session): bool
    {
        if ($this->creditedToday($session->user_id)->count() < $this->dailyLimit()) {
            return false;
        }

        $this->rejectSession($session, ['reason' => 'daily_cap_reached'], 'Rewarded ad SSV callback rejected: daily cap already reached.');

        return true;
    }

    /**
     * @param  array<string, mixed>|null  $metadata
     */
    private function rejectSession(AdRewardSession $session, ?array $metadata, string $logMessage): void
    {
        $attributes = ['status' => AdRewardSessionStatus::Expired];

        if ($metadata !== null) {
            $attributes['metadata'] = $metadata;
        }

        $session->update($attributes);

        Log::warning($logMessage, [
            'ad_reward_session_id' => $session->id,
            'user_id' => $session->user_id,
        ]);
    }

    private function creditSession(AdRewardSession $session, array $ssvParams): void
    {
        $transaction = $this->wallets->credit(
            $session->user,
            $session->reward_amount,
            WalletTransactionType::Reward,
            reference: $session,
            description: 'Rewarded ad watched',
            idempotencyKey: "ad-reward-session-{$session->id}",
        );

        $session->update([
            'status' => AdRewardSessionStatus::Credited,
            'wallet_transaction_id' => $transaction->id,
            'credited_at' => now(),
            'ad_network_transaction_id' => $ssvParams['transaction_id'] ?? null,
            'metadata' => Arr::only($ssvParams, ['ad_network', 'ad_unit', 'reward_item', 'timestamp']) ?: null,
        ]);
    }

    private function enabled(): bool
    {
        return (bool) config('services.admob.rewarded_ads_enabled', true);
    }

    private function dailyLimit(): int
    {
        return (int) config('services.admob.daily_token_limit', 15);
    }

    private function tokensPerAd(): int
    {
        return (int) config('services.admob.tokens_per_completed_ad', 1);
    }

    /**
     * @return Builder<AdRewardSession>
     */
    private function creditedToday(int $userId)
    {
        return AdRewardSession::query()
            ->where('user_id', $userId)
            ->where('status', AdRewardSessionStatus::Credited)
            ->whereDate('credited_at', today());
    }
}
