<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReactionRequest;
use App\Models\GameSession;
use App\Services\Game\ReactionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReactionController extends Controller
{
    public function __construct(private readonly ReactionService $reactions) {}

    public function store(StoreReactionRequest $request, GameSession $gameSession): JsonResponse
    {
        $this->authorize('viewGame', $gameSession->party);

        $this->reactions->send($request->user(), $gameSession, $request->validated('emoji'));

        return ApiResponse::success(null, 'Reaction sent.');
    }
}
