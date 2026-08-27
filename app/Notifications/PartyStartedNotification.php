<?php

namespace App\Notifications;

use App\Models\Party;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class PartyStartedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Party $party,
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
                'Party started',
                "\"{$this->party->title}\" has started!",
            ))
            ->withData([
                'type' => 'party.started',
                'party_id' => (string) $this->party->id,
            ]);
    }
}
