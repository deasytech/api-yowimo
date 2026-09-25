<?php

namespace App\Notifications;

use App\Models\WalletTransaction;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WalletCreditedNotification extends Notification implements ShouldQueue
{
    use DeliversViaFcmAndInApp, Queueable;

    public function __construct(
        public readonly WalletTransaction $transaction,
    ) {}

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        return [
            'title' => 'Wallet credited',
            'body' => "You received {$this->transaction->amount} {$this->transaction->wallet->currency}.",
            'type' => 'wallet.credited',
            'metadata' => [
                'wallet_transaction_id' => $this->transaction->id,
            ],
        ];
    }
}
