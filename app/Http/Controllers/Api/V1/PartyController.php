<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexPartyRequest;
use App\Http\Requests\Api\V1\StorePartyRequest;
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

        $party = $this->parties->create($request->user(), $request->validated());

        return ApiResponse::success(new PartyResource($party), 'Party created successfully.', 201);
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $party = $this->parties->find($id, $request->user());

        $this->authorize('view', $party);

        return ApiResponse::success(new PartyResource($party), 'Party retrieved successfully.');
    }
}
