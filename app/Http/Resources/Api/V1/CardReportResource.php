<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CardReport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CardReport */
class CardReportResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'pack_card_id' => $this->pack_card_id,
            'reason' => $this->reason->value,
            'note' => $this->note,
            'created_at' => $this->created_at,
        ];
    }
}
