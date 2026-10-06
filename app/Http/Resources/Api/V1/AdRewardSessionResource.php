<?php

namespace App\Http\Resources\Api\V1;

use App\Models\AdRewardSession;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Exposes only what the client needs to attach to the ad request —
 * never the session's row id or status, which aren't the client's concern
 * and aren't meant to be inspectable from the outside.
 */
class AdRewardSessionResource extends JsonResource
{
    public function __construct(AdRewardSession $resource, private readonly string $plaintextToken)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->plaintextToken,
            'expires_at' => $this->expires_at,
        ];
    }
}
