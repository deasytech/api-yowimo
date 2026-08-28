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

class WalletDebitedNotification extends Notification implements ShouldQueue
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
        $payload = $this->payload();

        return CloudMessage::new()
            ->withNotification(FcmNotification::create($payload['title'], $payload['body']))
            ->withData(['type' => $payload['type'], ...array_map('strval', $payload['metadata'])]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInApp(object $notifiable): array
    {
        return $this->payload();
    }

    /**
     * Shared title/body/type/metadata for both the FCM and in-app channels —
     * kept in one place so the two deliveries can't drift on content, only on
     * how each channel formats/casts it (FCM's `withData()` requires strings).
     *
     * @return array<string, mixed>
     */
    private function payload(): array
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
