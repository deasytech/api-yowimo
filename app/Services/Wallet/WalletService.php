<?php

namespace App\Services\Wallet;

use App\Enums\WalletTransactionType;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Exceptions\Api\DuplicatePaymentReferenceException;
use App\Exceptions\Api\IdempotencyKeyConflictException;
use App\Exceptions\Api\InsufficientWalletBalanceException;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WalletService
{
    /**
     * Get the user's wallet, creating it lazily on first use.
     */
    public function walletFor(User $user): Wallet
    {
        $wallet = $user->wallet()->first();

        if ($wallet) {
            return $wallet;
        }

        try {
            return $user->wallet()->create(['balance' => 0, 'currency' => 'tokens']);
        } catch (QueryException $exception) {
            if (! $this->isUserIdUniqueViolation($exception)) {
                throw $exception;
            }

            // Lost a create race to a concurrent request; the row now exists.
            return $user->wallet()->firstOrFail();
        }
    }

    /**
     * The cached balance. This is a performance read path only — the ledger
     * in wallet_transactions remains the source of truth (see recalculate()).
     */
    public function balance(User $user): int
    {
        return $this->walletFor($user)->balance;
    }

    /**
     * Credit tokens to a wallet (top-ups, refunds, bonuses, admin credits).
     *
     * @throws DuplicatePaymentReferenceException if $paymentReference was already used on a different entry.
     */
    public function credit(
        User $user,
        int $amount,
        WalletTransactionType $type,
        ?Model $reference = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
        ?string $paymentReference = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        return $this->applyEntry($user, $amount, $type, $reference, $description, $idempotencyKey, $paymentReference);
    }

    /**
     * Debit tokens from a wallet (purchases, unlocks, admin corrections).
     *
     * @throws InsufficientWalletBalanceException if the wallet does not have enough balance.
     */
    public function debit(
        User $user,
        int $amount,
        WalletTransactionType $type,
        ?Model $reference = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Debit amount must be positive.');
        }

        return $this->applyEntry($user, -$amount, $type, $reference, $description, $idempotencyKey);
    }

    /**
     * Rebuild the cached balance purely from the ledger. This is the proof
     * (and the recovery path) that wallets.balance is a derived cache, not
     * the source of truth.
     */
    public function recalculate(User $user): int
    {
        return DB::transaction(function () use ($user) {
            $wallet = Wallet::query()->whereKey($this->walletFor($user)->id)->lockForUpdate()->firstOrFail();

            $ledgerBalance = (int) $wallet->transactions()->sum('amount');

            $wallet->update(['balance' => $ledgerBalance]);

            return $ledgerBalance;
        });
    }

    private function applyEntry(
        User $user,
        int $signedAmount,
        WalletTransactionType $type,
        ?Model $reference,
        ?string $description,
        ?string $idempotencyKey,
        ?string $paymentReference = null,
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $signedAmount, $type, $reference, $description, $idempotencyKey, $paymentReference) {
            $wallet = Wallet::query()->whereKey($this->walletFor($user)->id)->lockForUpdate()->firstOrFail();

            $newBalance = $wallet->balance + $signedAmount;

            if ($newBalance < 0) {
                throw new InsufficientWalletBalanceException;
            }

            try {
                $transaction = WalletTransaction::create([
                    'wallet_id' => $wallet->id,
                    'type' => $type,
                    'amount' => $signedAmount,
                    'balance_after' => $newBalance,
                    'reference_type' => $reference?->getMorphClass(),
                    'reference_id' => $reference?->getKey(),
                    'idempotency_key' => $idempotencyKey,
                    'payment_reference' => $paymentReference,
                    'description' => $description,
                ]);
            } catch (QueryException $exception) {
                if ($paymentReference !== null && $this->isPaymentReferenceUniqueViolation($exception)) {
                    // A different attempt — possibly a different wallet
                    // entirely — already claimed this exact gateway
                    // reference. Unlike an idempotency_key collision, this
                    // is never safe to resolve by returning the other
                    // entry: it would either hand back an unrelated user's
                    // transaction or silently double-credit a single
                    // real-world charge that got resubmitted under a new
                    // idempotency_key.
                    throw new DuplicatePaymentReferenceException;
                }

                if ($idempotencyKey === null || ! $this->isIdempotencyKeyUniqueViolation($exception)) {
                    throw $exception;
                }

                // Concurrent retry of the same operation (e.g. a webhook); return
                // the entry that won the race instead of applying it twice. Scoped
                // to this wallet — the unique constraint is per-wallet, so this
                // can only be this user's own prior entry.
                $existing = WalletTransaction::query()
                    ->where('wallet_id', $wallet->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->firstOrFail();

                if ($existing->reference_type !== $reference?->getMorphClass()
                    || (string) $existing->reference_id !== (string) $reference?->getKey()) {
                    throw new IdempotencyKeyConflictException;
                }

                return $existing;
            }

            $wallet->update(['balance' => $newBalance]);

            $signedAmount >= 0
                ? WalletCredited::dispatch($transaction->id, $user->id)
                : WalletDebited::dispatch($transaction->id, $user->id);

            return $transaction;
        });
    }

    private function isUserIdUniqueViolation(QueryException $exception): bool
    {
        return $this->isUniqueViolationFor($exception, ['wallets_user_id_unique', 'user_id']);
    }

    private function isIdempotencyKeyUniqueViolation(QueryException $exception): bool
    {
        return $this->isUniqueViolationFor($exception, ['idempotency_key']);
    }

    private function isPaymentReferenceUniqueViolation(QueryException $exception): bool
    {
        return $this->isUniqueViolationFor($exception, ['payment_reference']);
    }

    /**
     * @param  array<int, string>  $needles
     */
    private function isUniqueViolationFor(QueryException $exception, array $needles): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $matchesColumn = Str::contains($message, $needles);
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $matchesColumn && $isUniqueViolation;
    }
}
