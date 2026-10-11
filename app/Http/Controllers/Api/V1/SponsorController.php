<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexSponsorshipsRequest;
use App\Http\Resources\Api\V1\SponsorshipResource;
use App\Http\Resources\Api\V1\SponsorSummaryResource;
use App\Services\Sponsorship\SponsorshipService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SponsorController extends Controller
{
    public function __construct(private readonly SponsorshipService $sponsorships) {}

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new SponsorSummaryResource($this->sponsorships->summaryFor($request->user())),
            'Sponsor summary retrieved successfully.'
        );
    }

    public function sponsorships(IndexSponsorshipsRequest $request): JsonResponse
    {
        $sponsorships = $this->sponsorships->listPaidBy($request->user(), $request->validated());

        return ApiResponse::paginated(
            SponsorshipResource::collection($sponsorships),
            $sponsorships,
            'Sponsorships retrieved successfully.'
        );
    }
}
