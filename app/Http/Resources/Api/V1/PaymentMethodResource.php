<?php

namespace App\Http\Resources\Api\V1;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Deliberately never exposes `authorization_code` — it's the sensitive
 * token Paystack uses to actually charge the card, not a display value.
 *
 * @mixin PaymentMethod
 */
class PaymentMethodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'card_type' => $this->card_type,
            'last4' => $this->last4,
            'exp_month' => $this->exp_month,
            'exp_year' => $this->exp_year,
            'bank' => $this->bank,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at,
        ];
    }
}
