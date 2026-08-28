<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Notification */
class NotificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'metadata' => $this->metadata,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
