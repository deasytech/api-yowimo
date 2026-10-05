<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin array{watched_today: int, daily_cap: int, remaining: int, next_reset_at: Carbon, enabled: bool, tokens_per_ad: int, can_earn: bool, server_date: string}
 */
class AdRewardProgressResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'watched_today' => $this->resource['watched_today'],
            'daily_cap' => $this->resource['daily_cap'],
            'remaining' => $this->resource['remaining'],
            'next_reset_at' => $this->resource['next_reset_at'],
            'enabled' => $this->resource['enabled'],
            'tokens_per_ad' => $this->resource['tokens_per_ad'],
            'can_earn' => $this->resource['can_earn'],
            'server_date' => $this->resource['server_date'],
        ];
    }
}
