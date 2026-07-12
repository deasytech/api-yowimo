<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Clerk\ClerkWebhookHandler;
use App\Services\Clerk\ClerkWebhookVerifier;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClerkWebhookController extends Controller
{
    public function __construct(
        private readonly ClerkWebhookVerifier $verifier,
        private readonly ClerkWebhookHandler $handler,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->verifier->verify($request);

        $this->handler->handle((string) $request->header('svix-id'), $payload);

        return ApiResponse::success(message: 'Webhook processed.');
    }
}
