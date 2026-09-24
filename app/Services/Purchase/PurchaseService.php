<?php

namespace App\Services\Purchase;

use App\Enums\WalletTransactionType;
use App\Events\PurchaseCompleted;
use App\Exceptions\Api\IdempotencyKeyConflictException;
use App\Exceptions\Api\PaymentDeclinedException;
use App\Models\PaymentMethod;
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
     * Exactly one of $paymentMethod or $paymentReference is normally given
     * (see PaymentProvider::charge()); both are optional so the manual/test
     * driver keeps working with neither, for local/CI use with no real
     * gateway configured.
     *
     * @throws PaymentDeclinedException if the payment provider declines the charge.
     */
    public function purchase(
        User $user,
        TokenBundle $bundle,
        string $idempotencyKey,
        ?PaymentMethod $paymentMethod = null,
        ?string $paymentReference = null,
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $bundle, $idempotencyKey, $paymentMethod, $paymentReference) {
            // Lock the wallet row first so a concurrent retry with the same
            // idempotency key blocks here until this one commits, then re-checks
            // below instead of racing the payment provider into a double charge.
            $wallet = Wallet::query()->whereKey($this->wallets->walletFor($user)->id)->lockForUpdate()->firstOrFail();

            // Scoped to this wallet: two different users' requests must never
            // collide on the same idempotency key.
            $existing = WalletTransaction::query()
                ->where('wallet_id', $wallet->id)
                ->where('idempotency_key', $idempotencyKey)
                ->first();

            if ($existing) {
                if ($existing->reference_type !== $bundle->getMorphClass()
                    || (string) $existing->reference_id !== (string) $bundle->getKey()) {
                    throw new IdempotencyKeyConflictException;
                }

                return $existing;
            }

            // Known gap: a failure between the charge succeeding at Paystack
            // and this transaction committing would leave no record of it
            // here, so a client retry (new idempotency key or none at all)
            // could charge the card again. PaystackPaymentProvider's
            // charge-by-reference path is naturally guarded against this for
            // the *client's* retry, since verifying the same reference twice
            // just confirms the same already-completed charge rather than
            // creating a new one — but a saved-method charge started fresh
            // here has no such protection yet. Fix then by passing
            // $idempotencyKey through as Paystack's own transaction
            // reference, so a retried saved-method charge resolves to the
            // original transaction instead of charging again.
            if (! $this->paymentProvider->charge($user, $bundle, $paymentMethod, $paymentReference)) {
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
