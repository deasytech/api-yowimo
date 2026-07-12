<?php

use App\Http\Controllers\Api\V1\ClerkWebhookController;
use App\Http\Controllers\Api\V1\MeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:clerk', 'throttle:api'])->group(function () {
        Route::get('/users/me', [MeController::class, 'show']);
        Route::patch('/users/me', [MeController::class, 'update']);
    });

    Route::post('/webhooks/clerk', ClerkWebhookController::class)->middleware('throttle:webhooks');
});
