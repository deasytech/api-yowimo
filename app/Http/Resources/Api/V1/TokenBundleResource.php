<?php

namespace App\Http\Resources\Api\V1;

use App\Models\TokenBundle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TokenBundle */
class TokenBundleResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'tokens' => $this->tokens,
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'badge' => $this->badge,
            'gradient' => $this->gradient ?? [],
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
