<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SponsorshipInvite;
use App\Support\StoredImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SponsorshipInvite */
class SponsorshipInviteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'url' => rtrim(config('services.sponsorship.web_url'), '/').'/'.$this->token,
            'scope' => $this->scope->value,
            'amount' => $this->amount,
            'status' => $this->effectiveStatus()->value,
            'expires_at' => $this->expires_at,
            'party' => [
                'id' => $this->party->id,
                'title' => $this->party->title,
                'max_players' => $this->party->max_players,
                'entry_fee' => $this->party->entry_fee,
                'cover_image_url' => StoredImageUrl::resolve($this->party->cover_image_url),
                'gradient' => $this->party->gradient ?? [],
                'host' => PartyHostResource::make($this->party->host),
            ],
        ];
    }
}
