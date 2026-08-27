<?php

use App\Enums\WalletTransactionType;
use App\Listeners\SendWalletCreditedPushNotification;
use App\Models\PushToken;
use App\Models\TokenBundle;
use App\Models\User;
use App\Notifications\PurchaseCompletedNotification;
use App\Notifications\WalletCreditedNotification;
use App\Services\Purchase\PurchaseService;
use App\Services\Wallet\WalletService;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Kreait\Firebase\Contract\Messaging;

it('pushes the wallet-credited notification listener onto the queue when WalletCredited fires', function () {
    Queue::fake();

    $user = User::factory()->create();
    app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);

    Queue::assertPushed(CallQueuedListener::class, fn ($job) => $job->class === SendWalletCreditedPushNotification::class);
});

it('notifies the credited user via FCM when WalletCredited fires', function () {
    Notification::fake();

    $user = User::factory()->create();
    $transaction = app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);

    Notification::assertSentTo($user, WalletCreditedNotification::class, fn ($notification) => $notification->transaction->id === $transaction->id);
});

it('actually delivers the wallet-credited push notification through a real queue worker', function () {
    config(['queue.default' => 'database']);

    $user = User::factory()->create();
    PushToken::factory()->create(['user_id' => $user->id, 'token' => 'real-queue-device-token']);

    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldReceive('send')
        ->once()
        ->withArgs(fn ($message) => $message->jsonSerialize()['token'] === 'real-queue-device-token')
        ->andReturn([]);
    app()->instance(Messaging::class, $messaging);

    app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);

    expect(DB::table('jobs')->count())->toBeGreaterThan(0);

    $this->artisan('queue:work', ['--stop-when-empty' => true])->run();

    expect(DB::table('jobs')->count())->toBe(0);
});

it('does not send the generic wallet-credited notification for a purchase-linked credit, since PurchaseCompleted already covers it', function () {
    Notification::fake();

    $user = User::factory()->create();
    $bundle = TokenBundle::factory()->create();

    app(PurchaseService::class)->purchase($user, $bundle, 'suppression_test_1');

    Notification::assertSentTo($user, PurchaseCompletedNotification::class);
    Notification::assertNotSentTo($user, WalletCreditedNotification::class);
});

it('does not call FCM when the credited user has no push token registered', function () {
    config(['queue.default' => 'database']);

    $user = User::factory()->create();

    $messaging = Mockery::mock(Messaging::class);
    $messaging->shouldNotReceive('send');
    app()->instance(Messaging::class, $messaging);

    app(WalletService::class)->credit($user, 100, WalletTransactionType::TopUp);

    $this->artisan('queue:work', ['--stop-when-empty' => true])->run();

    expect(DB::table('jobs')->count())->toBe(0);
});
