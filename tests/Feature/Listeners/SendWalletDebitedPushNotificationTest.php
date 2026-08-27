<?php

use App\Enums\WalletTransactionType;
use App\Listeners\SendWalletDebitedPushNotification;
use App\Models\Pack;
use App\Models\User;
use App\Notifications\PurchaseCompletedNotification;
use App\Notifications\WalletDebitedNotification;
use App\Services\Purchase\PackPurchaseService;
use App\Services\Wallet\WalletService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('pushes the wallet-debited notification listener onto the queue when WalletDebited fires', function () {
    $user = User::factory()->create();
    app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);

    Queue::fake();

    app(WalletService::class)->debit($user, 40, WalletTransactionType::Purchase);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendWalletDebitedPushNotification::class);
});

it('notifies the debited user via FCM when WalletDebited fires', function () {
    $user = User::factory()->create();
    app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);

    Notification::fake();

    $transaction = app(WalletService::class)->debit($user, 40, WalletTransactionType::Purchase);

    Notification::assertSentTo($user, WalletDebitedNotification::class, fn ($notification) => $notification->transaction->id === $transaction->id);
});

it('does not send the generic wallet-debited notification for a purchase-linked debit, since PurchaseCompleted already covers it', function () {
    $user = User::factory()->create();
    app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);
    $pack = Pack::factory()->create(['price' => 50]);

    Notification::fake();

    app(PackPurchaseService::class)->purchase($user, $pack, 'suppression_test_1');

    Notification::assertSentTo($user, PurchaseCompletedNotification::class);
    Notification::assertNotSentTo($user, WalletDebitedNotification::class);
});
