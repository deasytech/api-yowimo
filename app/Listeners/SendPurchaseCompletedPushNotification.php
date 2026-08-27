<?php

namespace App\Listeners;

use App\Events\PurchaseCompleted;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\PurchaseCompletedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendPurchaseCompletedPushNotification implements ShouldQueue
{
    public function handle(PurchaseCompleted $event): void
    {
        $user = User::find($event->userId);
        $transaction = WalletTransaction::find($event->walletTransactionId);

        if (! $user || ! $transaction) {
            return;
        }

        $user->notify(new PurchaseCompletedNotification($transaction));
    }
}
