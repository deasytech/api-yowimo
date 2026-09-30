<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\AccountDeletionService;
use App\Services\UserProfileService;
use App\Services\UserStatsService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(
        private readonly UserProfileService $profiles,
        private readonly UserStatsService $stats,
    ) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()), 'Profile retrieved successfully.');
    }

    /**
     * Profile screen stat tiles. A separate endpoint rather than folding
     * this into show() — GET /users/me is called constantly as a plain
     * auth/session check, and that should stay a single-row lookup rather
     * than pick up extra aggregate queries on every call.
     */
    public function stats(Request $request): JsonResponse
    {
        return ApiResponse::success($this->stats->forUser($request->user()), 'Profile stats retrieved successfully.');
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profiles->updateProfile($request->user(), $request->validated(), $request->file('avatar'));

        return ApiResponse::success(new UserResource($user), 'Profile updated successfully.');
    }

    public function destroy(Request $request, AccountDeletionService $accounts): JsonResponse
    {
        $accounts->delete($request->user());

        return ApiResponse::success(null, 'Account deleted successfully.');
    }
}
