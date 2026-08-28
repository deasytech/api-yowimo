<?php

namespace App\Notifications;

use App\Models\Party;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use App\Notifications\Channels\InAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class PartyMemberJoinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Party $party,
        public readonly User $joiningUser,
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
        $joiningUserName = $this->joiningUser->display_name ?: $this->joiningUser->username;

        return CloudMessage::new()
            ->withNotification(FcmNotification::create(
                'New party member',
                "{$joiningUserName} joined your party \"{$this->party->title}\".",
            ))
            ->withData([
                'type' => 'party.member_joined',
                'party_id' => (string) $this->party->id,
                'user_id' => (string) $this->joiningUser->id,
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInApp(object $notifiable): array
    {
        $joiningUserName = $this->joiningUser->display_name ?: $this->joiningUser->username;

        return [
            'title' => 'New party member',
            'body' => "{$joiningUserName} joined your party \"{$this->party->title}\".",
            'type' => 'party.member_joined',
            'metadata' => [
                'party_id' => $this->party->id,
                'user_id' => $this->joiningUser->id,
            ],
        ];
    }
}
