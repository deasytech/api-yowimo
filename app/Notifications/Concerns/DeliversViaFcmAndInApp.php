<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\FcmChannel;
use App\Notifications\Channels\InAppChannel;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

/**
 * Shared FCM + in-app delivery for notifications whose content is fully
 * described by payload(): title, body, type, and metadata. The two channels
 * only differ in how they format/cast that same content (FCM's withData()
 * requires strings).
 */
trait DeliversViaFcmAndInApp
{
    /**
     * @return array<int, string>
     */
    public function via(): array
    {
        return [FcmChannel::class, InAppChannel::class];
    }

    public function toFcm(): CloudMessage
    {
        $payload = $this->payload();

        return CloudMessage::new()
            ->withNotification(FcmNotification::create($payload['title'], $payload['body']))
            ->withData(['type' => $payload['type'], ...array_map('strval', $payload['metadata'])]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toInApp(): array
    {
        return $this->payload();
    }

    /**
     * @return array<string, mixed>
     */
    abstract private function payload(): array;
}
