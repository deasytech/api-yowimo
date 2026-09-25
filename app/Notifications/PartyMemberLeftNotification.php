<?php

namespace App\Notifications;

use App\Models\Party;
use App\Models\User;
use App\Notifications\Concerns\DeliversViaFcmAndInApp;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class PartyMemberLeftNotification extends Notification implements ShouldQueue
{
    use DeliversViaFcmAndInApp, Queueable;

    public function __construct(
        public readonly Party $party,
        public readonly User $leavingUser,
    ) {}

    /**
     * @return array<string, mixed>
     */
    private function payload(): array
    {
        $leavingUserName = $this->leavingUser->display_name ?: $this->leavingUser->username;

        return [
            'title' => 'Party member left',
            'body' => "{$leavingUserName} left your party \"{$this->party->title}\".",
            'type' => 'party.member.left',
            'metadata' => [
                'party_id' => $this->party->id,
                'user_id' => $this->leavingUser->id,
            ],
        ];
    }
}
