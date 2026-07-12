<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\UserProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(private readonly UserProfileService $profiles) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success(new UserResource($request->user()), 'Profile retrieved successfully.');
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->profiles->updateProfile($request->user(), $request->validated());

        return ApiResponse::success(new UserResource($user), 'Profile updated successfully.');
    }
}
