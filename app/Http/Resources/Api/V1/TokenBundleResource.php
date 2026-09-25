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
        // Nigerian buyers see (and are later charged) the NGN price when
        // one is set for this bundle; everyone else sees the default
        // price/currency. Same `price`/`currency` keys either way, so this
        // isn't a new field for API consumers to handle — just a value that
        // now varies by viewer.
        $price = $this->resource->priceFor($request->user());

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'tokens' => $this->tokens,
            'price' => $price['amount'],
            'currency' => $price['currency'],
            'badge' => $this->badge,
            'gradient' => $this->gradient ?? [],
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
