<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexLeaderboardRequest;
use App\Http\Resources\Api\V1\LeaderboardEntryResource;
use App\Services\Game\LeaderboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class LeaderboardController extends Controller
{
    public function __construct(private readonly LeaderboardService $leaderboards) {}

    public function index(IndexLeaderboardRequest $request): JsonResponse
    {
        $entries = $this->leaderboards->global($request->validated());

        return ApiResponse::paginated(
            LeaderboardEntryResource::collection($entries),
            $entries,
            'Leaderboard retrieved successfully.'
        );
    }
}
