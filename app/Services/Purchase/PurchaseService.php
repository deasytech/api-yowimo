<?php

namespace App\Services\Purchase;

use App\Enums\WalletTransactionType;
use App\Events\PurchaseCompleted;
use App\Exceptions\Api\DuplicatePaymentReferenceException;
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
     * @throws DuplicatePaymentReferenceException if $paymentReference was already used to credit a wallet.
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

            // A verified gateway reference is single-use, full stop — even
            // across different idempotency keys (which only dedupe retries of
            // the *same* logical attempt, not reuse of a reference that
            // already credited some wallet, possibly not even this one).
            // This upfront check is a fast fail; WalletService::credit()'s
            // unique constraint on payment_reference is the authoritative
            // guard against the race between two concurrent requests for
            // different wallets that this lock alone can't cover.
            if ($paymentReference !== null
                && WalletTransaction::query()->where('payment_reference', $paymentReference)->exists()) {
                throw new DuplicatePaymentReferenceException;
            }

            if (! $this->paymentProvider->charge($user, $bundle, $paymentMethod, $paymentReference, $idempotencyKey)) {
                throw new PaymentDeclinedException;
            }

            $transaction = $this->wallets->credit(
                $user,
                $bundle->tokens,
                WalletTransactionType::TopUp,
                reference: $bundle,
                description: "Purchased token bundle: {$bundle->name}",
                idempotencyKey: $idempotencyKey,
                paymentReference: $paymentReference,
            );

            PurchaseCompleted::dispatch($user->id, $bundle->getMorphClass(), $bundle->id, $transaction->id);

            return $transaction;
        });
    }
}
