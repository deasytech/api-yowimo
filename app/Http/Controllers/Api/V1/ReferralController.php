<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ClaimReferralRequest;
use App\Http\Resources\Api\V1\ReferralSummaryResource;
use App\Services\Referrals\ReferralService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referrals) {}

    public function claim(ClaimReferralRequest $request): JsonResponse
    {
        $this->referrals->claim($request->user(), $request->validated('code'));

        return ApiResponse::success(message: 'Referral code claimed successfully.');
    }

    public function summary(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new ReferralSummaryResource($this->referrals->summary($request->user())),
            'Referral summary retrieved successfully.'
        );
    }
}
