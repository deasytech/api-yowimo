<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PurchasePackRequest;
use App\Http\Resources\Api\V1\PackPurchaseResource;
use App\Services\PackService;
use App\Services\Purchase\PackPurchaseService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PackPurchaseController extends Controller
{
    public function __construct(
        private readonly PackService $packs,
        private readonly PackPurchaseService $purchases,
    ) {}

    public function store(PurchasePackRequest $request, int $id): JsonResponse
    {
        $pack = $this->packs->find($id);

        $this->authorize('view', $pack);

        $purchase = $this->purchases->purchase(
            $request->user(),
            $pack,
            $request->validated('idempotency_key'),
        );

        return ApiResponse::success(new PackPurchaseResource($purchase), 'Pack purchased successfully.', 201);
    }
}
