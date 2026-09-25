<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PurchaseTokenBundleRequest;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use App\Models\PaymentMethod;
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

        // Already scoped to the caller by the request's validation rule
        // (Rule::exists(...)->where('user_id', ...)), so no separate
        // ownership check is needed here.
        $paymentMethod = $request->validated('payment_method_id')
            ? PaymentMethod::find($request->validated('payment_method_id'))
            : null;

        $transaction = $this->purchases->purchase(
            $request->user(),
            $tokenBundle,
            $request->validated('idempotency_key'),
            $paymentMethod,
            $request->validated('payment_reference'),
        );

        return ApiResponse::success(
            new WalletTransactionResource($transaction),
            'Token bundle purchased successfully.',
            201
        );
    }
}
