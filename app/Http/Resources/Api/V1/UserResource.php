<?php

namespace App\Http\Resources\Api\V1;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'display_name' => $this->display_name,
            'avatar_url' => $this->avatar_url,
            'bio' => $this->bio,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'country_code' => $this->country_code,
            'interests' => $this->interests ?? [],
            'privacy_settings' => $this->privacy_settings ?? [],
            'status' => $this->status->value,
            'last_seen_at' => $this->last_seen_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            // Wallet phase isn't implemented yet; this is a stable placeholder shape.
            'wallet' => [
                'enabled' => false,
                'balance' => 0,
                'currency' => 'points',
            ],
        ];
    }
}
