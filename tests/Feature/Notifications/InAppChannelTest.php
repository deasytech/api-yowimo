<?php

use App\Models\Notification as NotificationModel;
use App\Models\User;
use App\Notifications\Channels\InAppChannel;
use Illuminate\Notifications\Notification;

it('persists a notification row when the notification implements toInApp', function () {
    $user = User::factory()->create();

    $notification = new class extends Notification
    {
        public function toInApp(object $notifiable): array
        {
            return [
                'title' => 'Test title',
                'body' => 'Test body',
                'type' => 'test.type',
                'metadata' => ['foo' => 'bar'],
            ];
        }
    };

    (new InAppChannel)->send($user, $notification);

    $row = NotificationModel::where('user_id', $user->id)->first();

    expect($row)->not->toBeNull()
        ->title->toBe('Test title')
        ->body->toBe('Test body')
        ->type->toBe('test.type')
        ->metadata->toBe(['foo' => 'bar'])
        ->read_at->toBeNull();
});

it('does nothing when the notification does not implement toInApp', function () {
    $user = User::factory()->create();

    $notification = new class extends Notification {};

    (new InAppChannel)->send($user, $notification);

    expect(NotificationModel::where('user_id', $user->id)->exists())->toBeFalse();
});
