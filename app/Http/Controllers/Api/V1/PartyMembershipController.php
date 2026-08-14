<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PartyResource;
use App\Models\Party;
use App\Services\Parties\PartyMembershipService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyMembershipController extends Controller
{
    public function __construct(private readonly PartyMembershipService $memberships) {}

    public function join(Request $request, Party $party): JsonResponse
    {
        $this->authorize('join', $party);

        $party = $this->memberships->join($request->user(), $party);

        return ApiResponse::success(new PartyResource($party), 'Joined party successfully.');
    }

    public function leave(Request $request, Party $party): JsonResponse
    {
        $this->authorize('leave', $party);

        $party = $this->memberships->leave($request->user(), $party);

        return ApiResponse::success(new PartyResource($party), 'Left party successfully.');
    }

    public function start(Request $request, Party $party): JsonResponse
    {
        $this->authorize('start', $party);

        $party = $this->memberships->start($party);

        return ApiResponse::success(new PartyResource($party), 'Party started successfully.');
    }

    public function end(Request $request, Party $party): JsonResponse
    {
        $this->authorize('end', $party);

        $party = $this->memberships->end($party);

        return ApiResponse::success(new PartyResource($party), 'Party ended successfully.');
    }
}
