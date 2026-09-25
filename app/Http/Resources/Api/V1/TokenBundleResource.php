<?php

namespace App\Http\Resources\Api\V1;

use App\Models\TokenBundle;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TokenBundle */
class TokenBundleResource extends JsonResource
{
    /**
     * `price`/`currency` are resolved per-viewer: the default (NGN, in
     * practice) for everyone, except a buyer confirmed to be outside
     * Nigeria sees `price_usd`/USD when this bundle has one set. See
     * TokenBundle::priceFor() for the exact rule — this always mirrors it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
