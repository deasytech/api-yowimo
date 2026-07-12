<?php

namespace App\Services\Wallet;

use App\Enums\WalletTransactionType;
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
     */
    public function credit(
        User $user,
        int $amount,
        WalletTransactionType $type,
        ?Model $reference = null,
        ?string $description = null,
        ?string $idempotencyKey = null,
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        return $this->applyEntry($user, $amount, $type, $reference, $description, $idempotencyKey);
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
    ): WalletTransaction {
        return DB::transaction(function () use ($user, $signedAmount, $type, $reference, $description, $idempotencyKey) {
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
                    'description' => $description,
                ]);
            } catch (QueryException $exception) {
                if ($idempotencyKey === null || ! $this->isIdempotencyKeyUniqueViolation($exception)) {
                    throw $exception;
                }

                // Concurrent retry of the same operation (e.g. a webhook); return
                // the entry that won the race instead of applying it twice.
                return WalletTransaction::query()->where('idempotency_key', $idempotencyKey)->firstOrFail();
            }

            $wallet->update(['balance' => $newBalance]);

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
