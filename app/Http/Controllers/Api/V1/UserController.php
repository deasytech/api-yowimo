<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PublicUserResource;
use App\Models\User;
use App\Services\Friends\BlockService;
use App\Services\Friends\FriendshipService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(
        private readonly BlockService $blocks,
        private readonly FriendshipService $friendships,
    ) {}

    /**
     * Another user's public profile. A deactivated user, or one where either
     * side has blocked the other, 404s exactly like a nonexistent id — so a
     * blocked user can't tell they've been blocked from this endpoint.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $viewer = $request->user();

        abort_if(
            $user->status === UserStatus::Deactivated || $this->blocks->isBlockedEitherWay($viewer, $user),
            404
        );

        $user->load(['badges' => fn ($query) => $query->with('badge')->orderByDesc('earned_at')->orderByDesc('id')]);

        return ApiResponse::success(
            new PublicUserResource($user, $this->friendships->between($viewer, $user)),
            'User profile retrieved successfully.'
        );
    }
}
