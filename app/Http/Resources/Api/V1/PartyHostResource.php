<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use App\Support\StoredImageUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class PartyHostResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'display_name' => $this->display_name,
            'avatar_url' => StoredImageUrl::resolve($this->avatar_url),
        ];
    }
}
