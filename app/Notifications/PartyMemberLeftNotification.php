<?php

namespace App\Notifications;

use App\Models\Party;
use App\Models\User;
use App\Notifications\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class PartyMemberLeftNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Party $party,
        public readonly User $leavingUser,
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
        $leavingUserName = $this->leavingUser->display_name ?: $this->leavingUser->username;

        return CloudMessage::new()
            ->withNotification(FcmNotification::create(
                'Party member left',
                "{$leavingUserName} left your party \"{$this->party->title}\".",
            ))
            ->withData([
                'type' => 'party.member.left',
                'party_id' => (string) $this->party->id,
                'user_id' => (string) $this->leavingUser->id,
            ]);
    }
}
