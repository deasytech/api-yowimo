<?php

namespace App\Listeners;

use App\Events\PartyCreated;
use App\Events\PartyMemberJoined;
use App\Events\PartyStarted;
use App\Events\PurchaseCompleted;
use App\Events\WalletCredited;
use App\Events\WalletDebited;
use App\Models\AnalyticsEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class RecordAnalyticsEvent implements ShouldQueue
{
    public function handle(PartyCreated|PartyMemberJoined|PartyStarted|WalletCredited|WalletDebited|PurchaseCompleted $event): void
    {
        [$userId, $payload] = match (true) {
            $event instanceof PartyCreated => [$event->hostId, [
                'party_id' => $event->partyId,
                'host_id' => $event->hostId,
            ]],
            $event instanceof PartyMemberJoined => [$event->userId, [
                'party_id' => $event->partyId,
                'user_id' => $event->userId,
            ]],
            $event instanceof PartyStarted => [null, [
                'party_id' => $event->partyId,
            ]],
            $event instanceof WalletCredited => [$event->userId, [
                'wallet_transaction_id' => $event->walletTransactionId,
                'user_id' => $event->userId,
            ]],
            $event instanceof WalletDebited => [$event->userId, [
                'wallet_transaction_id' => $event->walletTransactionId,
                'user_id' => $event->userId,
            ]],
            $event instanceof PurchaseCompleted => [$event->userId, [
                'user_id' => $event->userId,
                'reference_type' => $event->referenceType,
                'reference_id' => $event->referenceId,
                'wallet_transaction_id' => $event->walletTransactionId,
            ]],
        };

        AnalyticsEvent::create([
            'user_id' => $userId,
            'event' => class_basename($event),
            'payload' => $payload,
        ]);
    }
}
