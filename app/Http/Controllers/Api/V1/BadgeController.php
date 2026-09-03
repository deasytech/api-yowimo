<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBadgeRequest;
use App\Http\Requests\Api\V1\IndexUserBadgeRequest;
use App\Http\Resources\Api\V1\BadgeResource;
use App\Http\Resources\Api\V1\UserBadgeResource;
use App\Models\Badge;
use App\Services\Game\BadgeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class BadgeController extends Controller
{
    public function __construct(private readonly BadgeService $badges) {}

    public function index(IndexBadgeRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Badge::class);

        $badges = $this->badges->list($request->validated());

        return ApiResponse::paginated(
            BadgeResource::collection($badges),
            $badges,
            'Badges retrieved successfully.'
        );
    }

    public function mine(IndexUserBadgeRequest $request): JsonResponse
    {
        $earned = $request->user()->badges()
            ->with('badge')
            ->orderByDesc('earned_at')
            ->orderByDesc('id')
            ->cursorPaginate(
                perPage: min($request->validated('per_page') ?? 20, 50),
                cursor: $request->validated('cursor') ?? null,
            );

        return ApiResponse::paginated(
            UserBadgeResource::collection($earned),
            $earned,
            'Earned badges retrieved successfully.'
        );
    }
}
