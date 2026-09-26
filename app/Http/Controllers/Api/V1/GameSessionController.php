<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StartGameSessionRequest;
use App\Http\Resources\Api\V1\GameSessionResource;
use App\Http\Resources\Api\V1\GameStandingResource;
use App\Http\Resources\Api\V1\GameStateResource;
use App\Models\GameSession;
use App\Models\Party;
use App\Services\Game\GameResultsService;
use App\Services\Game\GameSessionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class GameSessionController extends Controller
{
    public function __construct(private readonly GameSessionService $sessions) {}

    public function start(StartGameSessionRequest $request, Party $party): JsonResponse
    {
        $this->authorize('manageGame', $party);

        $session = $this->sessions->start(
            $request->user(),
            $party,
            $request->integer('rounds') ?: null,
            $request->integer('turn_seconds') ?: null,
        );

        return ApiResponse::success(new GameSessionResource($session), 'Game session started successfully.');
    }

    public function show(GameSession $gameSession): JsonResponse
    {
        $this->authorize('viewGame', $gameSession->party);

        return ApiResponse::success(new GameStateResource($gameSession), 'Game session retrieved successfully.');
    }

    /**
     * The party's most recent game session (in progress or finished), so a
     * member can find the session id to load and subscribe to.
     */
    public function current(Party $party): JsonResponse
    {
        $this->authorize('viewGame', $party);

        $session = GameSession::query()->where('party_id', $party->id)->latest('id')->first();

        abort_unless($session, 404, 'This party has no game session yet.');

        return ApiResponse::success(new GameStateResource($session), 'Game session retrieved successfully.');
    }

    public function results(GameSession $gameSession, GameResultsService $results): JsonResponse
    {
        $this->authorize('viewGame', $gameSession->party);

        return ApiResponse::success([
            'game_session_id' => $gameSession->id,
            'status' => $gameSession->status->value,
            'standings' => GameStandingResource::collection($results->standings($gameSession)),
        ], 'Game results retrieved successfully.');
    }

    public function pause(GameSession $gameSession): JsonResponse
    {
        $this->authorize('manageGame', $gameSession->party);

        return ApiResponse::success(new GameStateResource($this->sessions->pause($gameSession)), 'Game paused.');
    }

    public function resume(GameSession $gameSession): JsonResponse
    {
        $this->authorize('manageGame', $gameSession->party);

        return ApiResponse::success(new GameStateResource($this->sessions->resume($gameSession)), 'Game resumed.');
    }

    public function nextTurn(GameSession $gameSession): JsonResponse
    {
        $this->authorize('manageGame', $gameSession->party);

        $session = $this->sessions->nextTurn($gameSession);

        return ApiResponse::success(new GameSessionResource($session), 'Advanced to the next turn.');
    }
}
