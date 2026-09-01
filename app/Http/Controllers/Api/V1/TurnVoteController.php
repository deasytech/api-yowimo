<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\VoteCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CastVoteRequest;
use App\Http\Resources\Api\V1\VoteResource;
use App\Models\GameSession;
use App\Models\Turn;
use App\Services\Game\VoteService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TurnVoteController extends Controller
{
    public function __construct(private readonly VoteService $votes) {}

    public function store(CastVoteRequest $request, GameSession $gameSession, Turn $turn): JsonResponse
    {
        abort_unless($turn->game_session_id === $gameSession->id, 404);

        $this->authorize('vote', $turn);

        $vote = $this->votes->cast(
            $request->user(),
            $turn,
            VoteCategory::from($request->string('category')->toString()),
        );

        return ApiResponse::success(new VoteResource($vote), 'Vote cast successfully.');
    }
}
