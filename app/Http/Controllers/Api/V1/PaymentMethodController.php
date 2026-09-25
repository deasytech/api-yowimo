<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentMethodResource;
use App\Models\PaymentMethod;
use App\Services\Purchase\PaymentMethodService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function __construct(private readonly PaymentMethodService $paymentMethods) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success(
            PaymentMethodResource::collection($this->paymentMethods->listFor($request->user())),
            'Payment methods retrieved successfully.'
        );
    }

    public function setDefault(PaymentMethod $paymentMethod, Request $request): JsonResponse
    {
        $this->authorize('manage', $paymentMethod);

        $this->paymentMethods->setDefault($request->user(), $paymentMethod);

        return ApiResponse::success(
            new PaymentMethodResource($paymentMethod->refresh()),
            'Default payment method updated successfully.'
        );
    }

    public function destroy(PaymentMethod $paymentMethod): JsonResponse
    {
        $this->authorize('manage', $paymentMethod);

        $this->paymentMethods->delete($paymentMethod);

        return ApiResponse::success(message: 'Payment method removed successfully.');
    }
}
