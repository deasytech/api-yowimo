<?php

namespace App\Notifications;

use App\Models\WalletTransaction;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\Channels\InAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class WalletCreditedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly WalletTransaction $transaction,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [FcmChannel::class, InAppChannel::class];
    }

    public function toFcm(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withNotification(FcmNotification::create(
                'Wallet credited',
                "You received {$this->transaction->amount} {$this->transaction->wallet->currency}.",
            ))
            ->withData([
                'type' => 'wallet.credited',
                'wallet_transaction_id' => (string) $this->transaction->id,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInApp(object $notifiable): array
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
