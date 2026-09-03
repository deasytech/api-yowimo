<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Badge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Badge */
class BadgeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key->value,
            'name' => $this->name,
            'description' => $this->description,
            'icon' => $this->icon,
            'created_at' => $this->created_at,
        ];
    }
}
