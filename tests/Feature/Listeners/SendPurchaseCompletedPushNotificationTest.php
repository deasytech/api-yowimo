<?php

use App\Listeners\SendPurchaseCompletedPushNotification;
use App\Models\TokenBundle;
use App\Models\User;
use App\Notifications\PurchaseCompletedNotification;
use App\Services\Purchase\PurchaseService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

it('pushes the purchase-completed notification listener onto the queue when PurchaseCompleted fires', function () {
    Queue::fake();

    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create();

    app(PurchaseService::class)->purchase($user, $bundle, 'push_test_purchase_1');

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendPurchaseCompletedPushNotification::class);
});

it('notifies the purchasing user via FCM when PurchaseCompleted fires', function () {
    Notification::fake();

    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create();

    $transaction = app(PurchaseService::class)->purchase($user, $bundle, 'push_test_purchase_2');

    Notification::assertSentTo($user, PurchaseCompletedNotification::class, fn ($notification) => $notification->transaction->id === $transaction->id);
});
