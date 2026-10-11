<?php

namespace App\Http\Resources\Api\V1;

use App\Enums\SponsorshipScope;
use App\Models\SponsorshipInvite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SponsorshipInvite */
class SponsorshipResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->effectiveStatus()->value,
            'covered_guest_count' => $this->scope === SponsorshipScope::FullParty
                ? max($this->party->max_players - 1, 0)
                : 0,
            'tokens_spent' => $this->amount,
            'paid_at' => $this->paid_at,
            'party' => PartyResource::make($this->whenLoaded('party')),
        ];
    }
}
