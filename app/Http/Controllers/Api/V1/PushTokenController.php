<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePushTokenRequest;
use App\Http\Resources\Api\V1\PushTokenResource;
use App\Services\Notifications\PushTokenService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushTokenController extends Controller
{
    public function __construct(private readonly PushTokenService $pushTokens) {}

    public function store(StorePushTokenRequest $request): JsonResponse
    {
        $pushToken = $this->pushTokens->register(
            $request->user(),
            $request->validated('token'),
            $request->validated('platform'),
        );

        return ApiResponse::success(new PushTokenResource($pushToken), 'Push token registered successfully.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $this->pushTokens->unregister($request->user());

        return ApiResponse::success(null, 'Push token removed successfully.');
    }
}
