<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AdRewardProgressResource;
use App\Http\Resources\Api\V1\AdRewardSessionResource;
use App\Services\Ads\AdRewardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdRewardSessionController extends Controller
{
    public function __construct(private readonly AdRewardService $adRewards) {}

    /**
     * Mints a session token for the frontend to attach as the rewarded ad's
     * SSV customData. Minting alone credits nothing — only a verified SSV
     * callback does (see AdRewardWebhookController).
     */
    public function store(Request $request): JsonResponse
    {
        $minted = $this->adRewards->mintSession($request->user());

        return ApiResponse::success(
            new AdRewardSessionResource($minted['model'], $minted['plaintext_token']),
            'Ad reward session created successfully.',
            201
        );
    }

    public function progress(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new AdRewardProgressResource($this->adRewards->dailyProgress($request->user())),
            'Ad reward progress retrieved successfully.'
        );
    }
}
