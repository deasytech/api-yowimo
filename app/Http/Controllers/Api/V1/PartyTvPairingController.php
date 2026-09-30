<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Services\Parties\TvPairingCodeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PartyTvPairingController extends Controller
{
    public function __construct(private readonly TvPairingCodeService $pairing) {}

    /**
     * Issues a short-lived code for pairing a TV/receiver app to this party.
     * The TV-side redemption flow (exchanging the code for access to the
     * party's realtime channel) isn't built yet — this only issues the code.
     */
    public function show(Party $party): JsonResponse
    {
        $this->authorize('pairTv', $party);

        $pairing = $this->pairing->forParty($party);

        return ApiResponse::success([
            'code' => $pairing['code'],
            'expires_at' => $pairing['expires_at'],
        ], 'TV pairing code issued successfully.');
    }
}
