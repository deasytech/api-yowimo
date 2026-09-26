<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\GameSessionResource;
use App\Models\GameSession;
use App\Models\Turn;
use App\Services\Game\GameSessionService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

/**
 * Actions the current player (or the host) takes on the open turn.
 */
class TurnActionController extends Controller
{
    public function __construct(private readonly GameSessionService $sessions) {}

    public function complete(GameSession $gameSession, Turn $turn): JsonResponse
    {
        abort_unless($turn->game_session_id === $gameSession->id, 404);

        $this->authorize('act', $turn);

        $session = $this->sessions->completeTurn($gameSession, $turn);

        return ApiResponse::success(new GameSessionResource($session), 'Turn completed.');
    }

    public function skip(GameSession $gameSession, Turn $turn): JsonResponse
    {
        abort_unless($turn->game_session_id === $gameSession->id, 404);

        $this->authorize('act', $turn);

        $session = $this->sessions->completeTurn($gameSession, $turn, skipped: true);

        return ApiResponse::success(new GameSessionResource($session), 'Turn skipped.');
    }
}
