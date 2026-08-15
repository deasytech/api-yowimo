<?php

namespace App\Services\Purchase;

use App\Enums\WalletTransactionType;
use App\Events\PurchaseCompleted;
use App\Exceptions\Api\PaymentDeclinedException;
use App\Models\TokenBundle;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class PurchaseService
{
    public function __construct(
        private readonly PaymentProvider $paymentProvider,
        private readonly WalletService $wallets,
    ) {}

    /**
     * Purchase a token bundle: charge the user, then credit the tokens to
     * their wallet via the existing ledger, idempotently.
     *
     * @throws PaymentDeclinedException if the payment provider declines the charge.
     */
    public function purchase(User $user, TokenBundle $bundle, string $idempotencyKey): WalletTransaction
    {
        return DB::transaction(function () use ($user, $bundle, $idempotencyKey) {
            // Lock the wallet row first so a concurrent retry with the same
            // idempotency key blocks here until this one commits, then re-checks
            // below instead of racing the payment provider into a double charge.
            Wallet::query()->whereKey($this->wallets->walletFor($user)->id)->lockForUpdate()->firstOrFail();

            $existing = WalletTransaction::query()->where('idempotency_key', $idempotencyKey)->first();

            if ($existing) {
                return $existing;
            }

            // Known gap: ManualPaymentProvider has no real side effect to lose
            // track of, but once a real gateway lands, a failure between this
            // charge succeeding and the transaction below committing would
            // leave no record of the charge, so a retry would charge again.
            // Fix then by passing $idempotencyKey to the gateway's own native
            // idempotency support rather than reconciling it ourselves.
            if (! $this->paymentProvider->charge($user, $bundle)) {
                throw new PaymentDeclinedException;
            }

            $transaction = $this->wallets->credit(
                $user,
                $bundle->tokens,
                WalletTransactionType::TopUp,
                reference: $bundle,
                description: "Purchased token bundle: {$bundle->name}",
                idempotencyKey: $idempotencyKey,
            );

            PurchaseCompleted::dispatch($user->id, $bundle->getMorphClass(), $bundle->id, $transaction->id);

            return $transaction;
        });
    }
}
