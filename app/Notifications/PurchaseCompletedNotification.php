<?php

namespace App\Notifications;

use App\Models\WalletTransaction;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class PurchaseCompletedNotification extends Notification implements ShouldQueue
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
        return [FcmChannel::class];
    }

    public function toFcm(object $notifiable): CloudMessage
    {
        return CloudMessage::new()
            ->withNotification(FcmNotification::create(
                'Purchase completed',
                $this->transaction->description ?? 'Your purchase completed successfully.',
            ))
            ->withData([
                'type' => 'purchase.completed',
                'reference_type' => (string) $this->transaction->reference_type,
                'reference_id' => (string) $this->transaction->reference_id,
                'wallet_transaction_id' => (string) $this->transaction->id,
            ]);
    }
}
