<?php

namespace App\Notifications;

use App\Models\Party;
use App\Models\User;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PartyMemberJoinedNotification extends Notification implements ShouldQueue
{
    use DeliversViaFcmAndInApp, Queueable;

    public function __construct(
        public readonly Party $party,
        public readonly User $joiningUser,
    ) {}

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
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
