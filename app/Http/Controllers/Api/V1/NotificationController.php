<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexNotificationRequest;
use App\Http\Requests\Api\V1\MarkNotificationReadRequest;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Services\Notifications\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(IndexNotificationRequest $request): JsonResponse
    {
        $notifications = $this->notifications->listFor(
            $request->user(),
            perPage: min($request->validated('per_page') ?? 20, 50),
            cursor: $request->validated('cursor') ?? null,
        );

        return ApiResponse::paginated(
            NotificationResource::collection($notifications),
            $notifications,
            'Notifications retrieved successfully.'
        );
    }

    public function markRead(MarkNotificationReadRequest $request): JsonResponse
    {
        $notification = $this->notifications->markRead($request->user(), $request->validated('notification_id'));

        return ApiResponse::success(new NotificationResource($notification), 'Notification marked as read.');
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->notifications->markAllRead($request->user());

        return ApiResponse::success(null, 'All notifications marked as read.');
    }
}
