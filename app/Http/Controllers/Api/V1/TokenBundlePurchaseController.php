<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PurchaseTokenBundleRequest;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use App\Services\Purchase\PurchaseService;
use App\Services\TokenBundleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class TokenBundlePurchaseController extends Controller
{
    public function __construct(
        private readonly TokenBundleService $tokenBundles,
        private readonly PurchaseService $purchases,
    ) {}

    public function store(PurchaseTokenBundleRequest $request, int $id): JsonResponse
    {
        $tokenBundle = $this->tokenBundles->find($id);

        $this->authorize('purchase', $tokenBundle);

        $transaction = $this->purchases->purchase(
            $request->user(),
            $tokenBundle,
            $request->validated('idempotency_key'),
        );

        return ApiResponse::success(
            new WalletTransactionResource($transaction),
            'Token bundle purchased successfully.',
            201
        );
    }
}
