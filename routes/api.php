<?php

use App\Http\Controllers\Api\V1\ClerkWebhookController;
use App\Http\Controllers\Api\V1\GameTypeController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PackController;
use App\Http\Controllers\Api\V1\PartyController;
use App\Http\Controllers\Api\V1\PartyLikeController;
use App\Http\Controllers\Api\V1\TokenBundleController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:clerk', 'throttle:api'])->group(function () {
        Route::get('/users/me', [MeController::class, 'show']);
        Route::patch('/users/me', [MeController::class, 'update']);

        Route::get('/game-types', [GameTypeController::class, 'index']);

        Route::get('/packs/featured', [PackController::class, 'featured']);
        Route::get('/packs/{id}', [PackController::class, 'show'])->whereNumber('id');
        Route::get('/packs', [PackController::class, 'index']);

        Route::get('/token-bundles', [TokenBundleController::class, 'index']);

        Route::get('/parties', [PartyController::class, 'index']);
        Route::post('/parties', [PartyController::class, 'store']);
        Route::get('/parties/{id}', [PartyController::class, 'show'])->whereNumber('id');
        Route::post('/parties/{party}/like', [PartyLikeController::class, 'store']);
        Route::delete('/parties/{party}/like', [PartyLikeController::class, 'destroy']);
    });

    Route::post('/webhooks/clerk', ClerkWebhookController::class)->middleware('throttle:webhooks');
});
