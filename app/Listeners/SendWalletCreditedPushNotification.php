<?php

namespace App\Listeners;

use App\Events\WalletCredited;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\WalletCreditedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWalletCreditedPushNotification implements ShouldQueue
{
    public function handle(WalletCredited $event): void
    {
        $user = User::find($event->userId);
        $transaction = WalletTransaction::find($event->walletTransactionId);

        if (! $user || ! $transaction) {
            return;
        }

        $user->notify(new WalletCreditedNotification($transaction));
    }
}
