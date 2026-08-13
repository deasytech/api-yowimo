<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexWalletTransactionRequest;
use App\Http\Resources\Api\V1\WalletResource;
use App\Http\Resources\Api\V1\WalletTransactionResource;
use App\Services\Wallet\WalletService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function __construct(private readonly WalletService $wallets) {}

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->wallets->walletFor($request->user());

        $this->authorize('view', $wallet);

        return ApiResponse::success(new WalletResource($wallet), 'Wallet retrieved successfully.');
    }

    public function transactions(IndexWalletTransactionRequest $request): JsonResponse
    {
        $wallet = $this->wallets->walletFor($request->user());

        $this->authorize('view', $wallet);

        $transactions = $wallet->transactions()
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(
                perPage: min($request->validated('per_page') ?? 20, 50),
                cursor: $request->validated('cursor') ?? null,
            );

        return ApiResponse::paginated(
            WalletTransactionResource::collection($transactions),
            $transactions,
            'Wallet transactions retrieved successfully.'
        );
    }
}
