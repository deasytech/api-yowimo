<?php

namespace App\Listeners;

use App\Events\WalletDebited;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Notifications\WalletDebitedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendWalletDebitedPushNotification implements ShouldQueue
{
    public function handle(WalletDebited $event): void
    {
        $user = User::find($event->userId);
        $transaction = WalletTransaction::find($event->walletTransactionId);

        if (! $user || ! $transaction) {
            return;
        }

        // Purchase-linked debits (reference_type set) already get a more specific
        // PurchaseCompletedNotification — skip the generic wallet notification here
        // to avoid double-notifying for the same purchase.
        if ($transaction->reference_type !== null) {
            return;
        }

        $user->notify(new WalletDebitedNotification($transaction));
    }
}
