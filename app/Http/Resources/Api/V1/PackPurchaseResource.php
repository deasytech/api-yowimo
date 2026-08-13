<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PackPurchase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PackPurchase */
class PackPurchaseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pack_id' => $this->pack_id,
            'wallet_transaction' => WalletTransactionResource::make($this->whenLoaded('walletTransaction')),
            'purchased_at' => $this->created_at,
        ];
    }
}
