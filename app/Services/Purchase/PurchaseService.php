<?php

namespace App\Services\Purchase;

use App\Enums\WalletTransactionType;
use App\Exceptions\Api\PaymentDeclinedException;
use App\Models\TokenBundle;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\Wallet\WalletService;

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
        if (! $this->paymentProvider->charge($user, $bundle)) {
            throw new PaymentDeclinedException;
        }

        return $this->wallets->credit(
            $user,
            $bundle->tokens,
            WalletTransactionType::TopUp,
            reference: $bundle,
            description: "Purchased token bundle: {$bundle->name}",
            idempotencyKey: $idempotencyKey,
        );
    }
}
