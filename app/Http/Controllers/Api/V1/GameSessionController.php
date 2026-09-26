<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StartGameSessionRequest;
use App\Http\Resources\Api\V1\GameSessionResource;
use App\Http\Resources\Api\V1\GameStateResource;
use App\Models\GameSession;
use App\Models\Party;
use App\Services\Game\GameSessionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class GameSessionController extends Controller
{
    public function __construct(private readonly GameSessionService $sessions) {}

    public function start(StartGameSessionRequest $request, Party $party): JsonResponse
    {
        $this->authorize('manageGame', $party);

        $session = $this->sessions->start($request->user(), $party, $request->integer('rounds') ?: null);

        return ApiResponse::success(new GameSessionResource($session), 'Game session started successfully.');
    }

    public function show(GameSession $gameSession): JsonResponse
    {
        $this->authorize('viewGame', $gameSession->party);

        return ApiResponse::success(new GameStateResource($gameSession), 'Game session retrieved successfully.');
    }

    public function nextTurn(GameSession $gameSession): JsonResponse
    {
        $this->authorize('manageGame', $gameSession->party);

        $session = $this->sessions->nextTurn($gameSession);

        return ApiResponse::success(new GameSessionResource($session), 'Advanced to the next turn.');
    }
}
