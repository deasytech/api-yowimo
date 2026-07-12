<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexGameTypeRequest;
use App\Http\Resources\Api\V1\GameTypeResource;
use App\Models\GameType;
use App\Services\GameTypeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class GameTypeController extends Controller
{
    public function __construct(private readonly GameTypeService $gameTypes) {}

    public function index(IndexGameTypeRequest $request): JsonResponse
    {
        $this->authorize('viewAny', GameType::class);

        $gameTypes = $this->gameTypes->list($request->validated());

        return ApiResponse::paginated(
            GameTypeResource::collection($gameTypes),
            $gameTypes,
            'Game types retrieved successfully.'
        );
    }
}
