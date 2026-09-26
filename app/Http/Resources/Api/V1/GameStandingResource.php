<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One player's row in a game's results, as built by GameResultsService::standings().
 *
 * @property array<string, mixed> $resource
 */
class GameStandingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'user' => new PartyHostResource($this->resource['user']),
            'xp' => $this->resource['xp'],
            'votes' => $this->resource['votes'],
            'turns' => $this->resource['turns'],
            'is_mvp' => $this->resource['is_mvp'],
        ];
    }
}
