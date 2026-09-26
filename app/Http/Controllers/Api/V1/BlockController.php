<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBlockRequest;
use App\Http\Resources\Api\V1\BlockedUserResource;
use App\Models\User;
use App\Services\Friends\BlockService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Every query here is scoped to the authenticated user as the blocker, so a
 * caller can only ever list, create, or remove their own blocks (same
 * scoped-query approach as PushTokenService rather than a separate Policy).
 */
class BlockController extends Controller
{
    public function __construct(private readonly BlockService $blocks) {}

    public function index(Request $request): JsonResponse
    {
        $blocked = $this->blocks->blockedBy($request->user());

        return ApiResponse::success(BlockedUserResource::collection($blocked), 'Blocked users retrieved successfully.');
    }

    public function store(StoreBlockRequest $request): JsonResponse
    {
        $blocked = User::findOrFail($request->validated('user_id'));

        $block = $this->blocks->block($request->user(), $blocked);

        return ApiResponse::success(
            new BlockedUserResource($block->load('blocked')),
            'User blocked successfully.',
            $block->wasRecentlyCreated ? 201 : 200
        );
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $this->blocks->unblock($request->user(), $user);

        return ApiResponse::success(null, 'User unblocked successfully.');
    }
}
