<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Services\Video\LiveKitTokenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyVideoController extends Controller
{
    public function __construct(private readonly LiveKitTokenService $liveKit) {}

    public function token(Request $request, Party $party): JsonResponse
    {
        $this->authorize('joinVideo', $party);

        return ApiResponse::success([
            'token' => $this->liveKit->tokenFor($request->user(), $party),
            'url' => config('services.livekit.url'),
            'room' => $this->liveKit->roomNameFor($party),
        ], 'Video token issued successfully.');
    }
}
