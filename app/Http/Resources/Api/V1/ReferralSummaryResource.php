<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{referral_code: string, referred_count: int, tokens_earned: int, reward_amount: int}
 */
class ReferralSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'referral_code' => $this->resource['referral_code'],
            'referred_count' => $this->resource['referred_count'],
            'tokens_earned' => $this->resource['tokens_earned'],
            'reward_amount' => $this->resource['reward_amount'],
        ];
    }
}
