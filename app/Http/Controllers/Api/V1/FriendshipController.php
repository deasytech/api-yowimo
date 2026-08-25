<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFriendshipRequest;
use App\Http\Resources\Api\V1\FriendResource;
use App\Http\Resources\Api\V1\FriendshipResource;
use App\Models\Friendship;
use App\Models\User;
use App\Services\Friends\FriendshipService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function __construct(private readonly FriendshipService $friendships) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Friendship::class);

        $friends = $this->friendships->friends($request->user());

        return ApiResponse::success(FriendResource::collection($friends), 'Friends retrieved successfully.');
    }

    public function pending(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Friendship::class);

        $requests = $this->friendships->pendingRequests($request->user());

        return ApiResponse::success(FriendshipResource::collection($requests), 'Pending friend requests retrieved successfully.');
    }

    public function store(StoreFriendshipRequest $request): JsonResponse
    {
        $this->authorize('create', Friendship::class);

        $receiver = User::findOrFail($request->validated('receiver_id'));

        $friendship = $this->friendships->send($request->user(), $receiver);

        return ApiResponse::success(
            new FriendshipResource($friendship->load(['sender', 'receiver'])),
            'Friend request sent successfully.',
            201
        );
    }

    public function accept(Request $request, Friendship $friendship): JsonResponse
    {
        $this->authorize('accept', $friendship);

        $friendship = $this->friendships->accept($friendship);

        return ApiResponse::success(
            new FriendshipResource($friendship->load(['sender', 'receiver'])),
            'Friend request accepted successfully.'
        );
    }

    public function reject(Request $request, Friendship $friendship): JsonResponse
    {
        $this->authorize('reject', $friendship);

        $friendship = $this->friendships->reject($friendship);

        return ApiResponse::success(
            new FriendshipResource($friendship->load(['sender', 'receiver'])),
            'Friend request rejected successfully.'
        );
    }

    public function cancel(Request $request, Friendship $friendship): JsonResponse
    {
        $this->authorize('cancel', $friendship);

        $friendship = $this->friendships->cancel($friendship);

        return ApiResponse::success(
            new FriendshipResource($friendship->load(['sender', 'receiver'])),
            'Friend request cancelled successfully.'
        );
    }

    public function destroy(Request $request, Friendship $friendship): JsonResponse
    {
        $this->authorize('remove', $friendship);

        $this->friendships->remove($friendship);

        return ApiResponse::success(null, 'Friend removed successfully.');
    }
}
