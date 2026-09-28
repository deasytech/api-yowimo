<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CardReportReason;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCardReportRequest;
use App\Http\Resources\Api\V1\CardReportResource;
use App\Models\CardReport;
use App\Models\PackCard;
use App\Services\Game\CardReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CardReportController extends Controller
{
    public function __construct(private readonly CardReportService $reports) {}

    public function store(StoreCardReportRequest $request, PackCard $card): JsonResponse
    {
        $this->authorize('create', CardReport::class);

        $report = $this->reports->report(
            $request->user(),
            $card,
            $request->enum('reason', CardReportReason::class),
            $request->validated('note'),
        );

        return ApiResponse::success(new CardReportResource($report), 'Card reported successfully.', 201);
    }
}
