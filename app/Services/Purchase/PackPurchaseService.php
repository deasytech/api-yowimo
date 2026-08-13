<?php

namespace App\Services\Purchase;

use App\Enums\WalletTransactionType;
use App\Exceptions\Api\InsufficientWalletBalanceException;
use App\Exceptions\Api\PackAlreadyOwnedException;
use App\Models\Pack;
use App\Models\PackPurchase;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

class PackPurchaseService
{
    public function __construct(private readonly WalletService $wallets) {}

    public function hasPurchased(User $user, Pack $pack): bool
    {
        return PackPurchase::query()
            ->where('pack_id', $pack->id)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Debit the pack's price from the user's wallet and record ownership.
     *
     * @throws PackAlreadyOwnedException if the user already owns the pack.
     * @throws InsufficientWalletBalanceException if the wallet balance is too low.
     */
    public function purchase(User $user, Pack $pack, string $idempotencyKey): PackPurchase
    {
        return DB::transaction(function () use ($user, $pack, $idempotencyKey) {
            // Lock the wallet row first so a concurrent purchase attempt for the same
            // user blocks here until this one commits, then re-checks ownership below
            // instead of racing WalletService::debit() into a double charge.
            Wallet::query()->whereKey($this->wallets->walletFor($user)->id)->lockForUpdate()->firstOrFail();

            if ($this->hasPurchased($user, $pack)) {
                throw new PackAlreadyOwnedException;
            }

            $transaction = $this->wallets->debit(
                $user,
                $pack->price,
                WalletTransactionType::Purchase,
                reference: $pack,
                description: "Purchased pack: {$pack->name}",
                idempotencyKey: $idempotencyKey,
            );

            $purchase = PackPurchase::create([
                'pack_id' => $pack->id,
                'user_id' => $user->id,
                'wallet_transaction_id' => $transaction->id,
            ]);

            $purchase->setRelation('walletTransaction', $transaction);

            return $purchase;
        });
    }
}
