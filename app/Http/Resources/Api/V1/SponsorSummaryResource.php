<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{players_covered: int, parties_count: int, tokens_spent: int}
 */
class SponsorSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'players_covered' => $this->resource['players_covered'],
            'parties_count' => $this->resource['parties_count'],
            'tokens_spent' => $this->resource['tokens_spent'],
        ];
    }
}
