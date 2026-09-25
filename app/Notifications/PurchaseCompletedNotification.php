<?php

namespace App\Notifications;

use App\Models\WalletTransaction;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PurchaseCompletedNotification extends Notification implements ShouldQueue
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
            'title' => 'Purchase completed',
            'body' => $this->transaction->description ?? 'Your purchase completed successfully.',
            'type' => 'purchase.completed',
            'metadata' => [
                'reference_type' => $this->transaction->reference_type,
                'reference_id' => $this->transaction->reference_id,
                'wallet_transaction_id' => $this->transaction->id,
            ],
        ];
    }
}
