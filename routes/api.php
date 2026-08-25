<?php

use App\Http\Controllers\Api\V1\ClerkWebhookController;
use App\Http\Controllers\Api\V1\FriendshipController;
use App\Http\Controllers\Api\V1\GameSessionController;
use App\Http\Controllers\Api\V1\GameTypeController;
use App\Http\Controllers\Api\V1\MeController;
use App\Http\Controllers\Api\V1\PackController;
use App\Http\Controllers\Api\V1\PackPurchaseController;
use App\Http\Controllers\Api\V1\PartyController;
use App\Http\Controllers\Api\V1\PartyLikeController;
use App\Http\Controllers\Api\V1\PartyMembershipController;
use App\Http\Controllers\Api\V1\PushTokenController;
use App\Http\Controllers\Api\V1\TokenBundleController;
use App\Http\Controllers\Api\V1\TokenBundlePurchaseController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::middleware(['auth:clerk', 'throttle:api'])->group(function () {
        Route::get('/users/me', [MeController::class, 'show']);
        Route::patch('/users/me', [MeController::class, 'update']);

        Route::get('/game-types', [GameTypeController::class, 'index']);

        Route::get('/packs/featured', [PackController::class, 'featured']);
        Route::get('/packs/{id}', [PackController::class, 'show'])->whereNumber('id');
        Route::get('/packs', [PackController::class, 'index']);
        Route::post('/packs/{id}/purchase', [PackPurchaseController::class, 'store'])->whereNumber('id');

        Route::get('/token-bundles', [TokenBundleController::class, 'index']);
        Route::post('/token-bundles/{id}/purchase', [TokenBundlePurchaseController::class, 'store'])->whereNumber('id');

        Route::get('/wallet', [WalletController::class, 'show']);
        Route::get('/wallet/transactions', [WalletController::class, 'transactions']);

        Route::get('/parties', [PartyController::class, 'index']);
        Route::post('/parties', [PartyController::class, 'store']);
        Route::get('/parties/{id}', [PartyController::class, 'show'])->whereNumber('id');
        Route::post('/parties/{party}/like', [PartyLikeController::class, 'store']);
        Route::delete('/parties/{party}/like', [PartyLikeController::class, 'destroy']);
        Route::post('/parties/{party}/join', [PartyMembershipController::class, 'join']);
        Route::delete('/parties/{party}/leave', [PartyMembershipController::class, 'leave']);
        Route::post('/parties/{party}/start', [PartyMembershipController::class, 'start']);
        Route::post('/parties/{party}/end', [PartyMembershipController::class, 'end']);
        Route::post('/parties/{party}/game/start', [GameSessionController::class, 'start']);
        Route::post('/game/{gameSession}/next-turn', [GameSessionController::class, 'nextTurn']);

        Route::post('/push-tokens', [PushTokenController::class, 'store']);
        Route::delete('/push-tokens', [PushTokenController::class, 'destroy']);

        Route::get('/friends', [FriendshipController::class, 'index']);
        Route::delete('/friends/{friendship}', [FriendshipController::class, 'destroy']);
        Route::get('/friend-requests', [FriendshipController::class, 'pending']);
        Route::post('/friend-requests', [FriendshipController::class, 'store']);
        Route::post('/friend-requests/{friendship}/accept', [FriendshipController::class, 'accept']);
        Route::post('/friend-requests/{friendship}/reject', [FriendshipController::class, 'reject']);
        Route::delete('/friend-requests/{friendship}', [FriendshipController::class, 'cancel']);
    });

    Route::post('/webhooks/clerk', ClerkWebhookController::class)->middleware('throttle:webhooks');
});
