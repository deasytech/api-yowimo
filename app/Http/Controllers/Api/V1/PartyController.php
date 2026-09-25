<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexHostedPartyRequest;
use App\Http\Requests\Api\V1\IndexJoinedPartyRequest;
use App\Http\Requests\Api\V1\IndexPartyRequest;
use App\Http\Requests\Api\V1\LookupPartyRequest;
use App\Http\Requests\Api\V1\StorePartyRequest;
use App\Http\Requests\Api\V1\UpdatePartyRequest;
use App\Http\Resources\Api\V1\PartyMembershipResource;
use App\Http\Resources\Api\V1\PartyResource;
use App\Models\Party;
use App\Services\Parties\PartyService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyController extends Controller
{
    public function __construct(private readonly PartyService $parties) {}

    public function index(IndexPartyRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Party::class);

        $parties = $this->parties->list($request->validated(), $request->user());

        return ApiResponse::paginated(
            PartyResource::collection($parties),
            $parties,
            'Parties retrieved successfully.'
        );
    }

    public function store(StorePartyRequest $request): JsonResponse
    {
        $this->authorize('create', Party::class);

        $party = $this->parties->create($request->user(), $request->validated(), $request->file('cover_image'));

        return ApiResponse::success(new PartyResource($party), 'Party created successfully.', 201);
    }

    /**
     * Resolves a room code to its party summary — the caller then joins via
     * the existing POST /parties/{party}/join with the resolved id. Not
     * gated by the same visibility rule as show(): a valid room code is its
     * own authorization, private parties included (see PartyService::findByRoomCode()).
     */
    public function lookup(LookupPartyRequest $request): JsonResponse
    {
        $party = $this->parties->findByRoomCode($request->string('room_code')->toString(), $request->user());

        return ApiResponse::success(new PartyResource($party), 'Party found.');
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $party = $this->parties->find($id, $request->user());

        $this->authorize('view', $party);

        return ApiResponse::success(new PartyResource($party), 'Party retrieved successfully.');
    }

    public function update(int $id, UpdatePartyRequest $request): JsonResponse
    {
        $party = Party::findOrFail($id);

        $this->authorize('update', $party);

        $party = $this->parties->update($party, $request->validated());

        return ApiResponse::success(new PartyResource($party), 'Party updated successfully.');
    }

    /**
     * Every party the caller hosts, any status/visibility — the host's own
     * management view, not the public discover feed.
     */
    public function hosted(IndexHostedPartyRequest $request): JsonResponse
    {
        $parties = $this->parties->listHostedBy($request->user(), $request->validated());

        return ApiResponse::paginated(
            PartyResource::collection($parties),
            $parties,
            'Hosted parties retrieved successfully.'
        );
    }

    /**
     * Every party the caller has ever been a member of (not host) — current
     * and past, each entry carrying its own membership record.
     */
    public function joined(IndexJoinedPartyRequest $request): JsonResponse
    {
        $memberships = $this->parties->listJoinedBy($request->user(), $request->validated());

        return ApiResponse::paginated(
            PartyMembershipResource::collection($memberships),
            $memberships,
            'Joined parties retrieved successfully.'
        );
    }
}
