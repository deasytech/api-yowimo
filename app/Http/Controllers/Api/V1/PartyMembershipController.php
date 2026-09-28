<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\JoinPartyRequest;
use App\Http\Resources\Api\V1\PartyPlayerResource;
use App\Http\Resources\Api\V1\PartyResource;
use App\Models\Party;
use App\Services\Parties\PartyMembershipService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyMembershipController extends Controller
{
    public function __construct(private readonly PartyMembershipService $memberships) {}

    public function players(Party $party): JsonResponse
    {
        $this->authorize('view', $party);

        $players = $this->memberships->players($party);

        return ApiResponse::success(PartyPlayerResource::collection($players), 'Party players retrieved successfully.');
    }

    public function join(JoinPartyRequest $request, Party $party): JsonResponse
    {
        $this->authorize('join', [$party, $request->validated('room_code')]);

        $party = $this->memberships->join($request->user(), $party);

        return ApiResponse::success(new PartyResource($party), 'Joined party successfully.');
    }

    public function leave(Request $request, Party $party): JsonResponse
    {
        $this->authorize('leave', $party);

        $party = $this->memberships->leave($request->user(), $party);

        return ApiResponse::success(new PartyResource($party), 'Left party successfully.');
    }

    public function ready(Request $request, Party $party): JsonResponse
    {
        $this->authorize('ready', $party);

        $membership = $this->memberships->ready($request->user(), $party);

        return ApiResponse::success(new PartyPlayerResource($membership->load('user')), 'Marked ready.');
    }

    public function unready(Request $request, Party $party): JsonResponse
    {
        $this->authorize('unready', $party);

        $membership = $this->memberships->unready($request->user(), $party);

        return ApiResponse::success(new PartyPlayerResource($membership->load('user')), 'Marked not ready.');
    }

    public function start(Party $party): JsonResponse
    {
        $this->authorize('start', $party);

        $party = $this->memberships->start($party);

        return ApiResponse::success(new PartyResource($party), 'Party started successfully.');
    }

    public function end(Party $party): JsonResponse
    {
        $this->authorize('end', $party);

        $party = $this->memberships->end($party);

        return ApiResponse::success(new PartyResource($party), 'Party ended successfully.');
    }

    public function cancel(Party $party): JsonResponse
    {
        $this->authorize('cancel', $party);

        $party = $this->memberships->cancel($party);

        return ApiResponse::success(new PartyResource($party), 'Party cancelled successfully.');
    }
}
