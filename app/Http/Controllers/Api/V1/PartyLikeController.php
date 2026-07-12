<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PartyResource;
use App\Models\Party;
use App\Services\Parties\PartyLikeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartyLikeController extends Controller
{
    public function __construct(private readonly PartyLikeService $likes) {}

    public function store(Request $request, Party $party): JsonResponse
    {
        $this->authorize('like', $party);

        $party = $this->likes->like($request->user(), $party);

        return ApiResponse::success(new PartyResource($party), 'Party liked successfully.');
    }

    public function destroy(Request $request, Party $party): JsonResponse
    {
        $this->authorize('unlike', $party);

        $party = $this->likes->unlike($request->user(), $party);

        return ApiResponse::success(new PartyResource($party), 'Party unliked successfully.');
    }
}
