<?php

namespace App\Http\Resources\Api\V1;

use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WalletTransaction */
class WalletTransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'description' => $this->description,
            'created_at' => $this->created_at,
        ];
    }
}
