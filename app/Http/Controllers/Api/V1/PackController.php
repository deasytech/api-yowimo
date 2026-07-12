<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexPackRequest;
use App\Http\Resources\Api\V1\PackResource;
use App\Models\Pack;
use App\Services\PackService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PackController extends Controller
{
    public function __construct(private readonly PackService $packs) {}

    public function index(IndexPackRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Pack::class);

        $packs = $this->packs->list($request->validated());

        return ApiResponse::paginated(
            PackResource::collection($packs),
            $packs,
            'Packs retrieved successfully.'
        );
    }

    public function featured(IndexPackRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Pack::class);

        $packs = $this->packs->featured($request->validated());

        return ApiResponse::paginated(
            PackResource::collection($packs),
            $packs,
            'Featured packs retrieved successfully.'
        );
    }

    public function show(int $id): JsonResponse
    {
        $pack = $this->packs->find($id);

        $this->authorize('view', $pack);

        return ApiResponse::success(new PackResource($pack), 'Pack retrieved successfully.');
    }
}
