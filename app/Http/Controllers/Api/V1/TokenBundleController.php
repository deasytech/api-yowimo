<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexTokenBundleRequest;
use App\Http\Resources\Api\V1\TokenBundleResource;
use App\Models\TokenBundle;
use App\Services\TokenBundleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TokenBundleController extends Controller
{
    public function __construct(private readonly TokenBundleService $tokenBundles) {}

    public function index(IndexTokenBundleRequest $request): JsonResponse
    {
        $this->authorize('viewAny', TokenBundle::class);

        $tokenBundles = $this->tokenBundles->list($request->validated());

        return ApiResponse::paginated(
            TokenBundleResource::collection($tokenBundles),
            $tokenBundles,
            'Token bundles retrieved successfully.'
        );
    }
}
