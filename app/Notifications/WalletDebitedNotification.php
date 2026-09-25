<?php

namespace App\Notifications;

use App\Models\WalletTransaction;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WalletDebitedNotification extends Notification implements ShouldQueue
{
    use DeliversViaFcmAndInApp, Queueable;

    public function __construct(
        public readonly WalletTransaction $transaction,
    ) {}

    /**
     * Shared title/body/type/metadata for both the FCM and in-app channels —
     * kept in one place so the two deliveries can't drift on content, only on
     * how each channel formats/casts it (FCM's `withData()` requires strings).
     *
     * @return array<string, mixed>
     */
    private function payload(): array // NOSONAR php:S1144 - satisfies DeliversViaFcmAndInApp::payload(), called via $this->payload() in the trait
    {
        return [
            'title' => 'Wallet debited',
            'body' => 'You spent '.abs($this->transaction->amount)." {$this->transaction->wallet->currency}.",
            'type' => 'wallet.debited',
            'metadata' => [
                'wallet_transaction_id' => $this->transaction->id,
            ],
        ];
    }
}
