<?php

use App\Exceptions\Api\AlreadyFriendsException;
use App\Exceptions\Api\DuplicateFriendRequestException;
use App\Exceptions\Api\IdempotencyKeyConflictException;
use App\Exceptions\Api\InsufficientWalletBalanceException;
use App\Exceptions\Api\InvalidClerkTokenException;
use App\Exceptions\Api\InvalidClerkWebhookException;
use App\Exceptions\Api\InvalidFriendshipTransitionException;
use App\Exceptions\Api\InvalidPartyTransitionException;
use App\Exceptions\Api\PackAlreadyOwnedException;
use App\Exceptions\Api\PartyFullException;
use App\Exceptions\Api\PartyHostCannotLeaveException;
use App\Exceptions\Api\PartyNotJoinableException;
use App\Exceptions\Api\PaymentDeclinedException;
use App\Support\ApiExceptionRegistrar;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['auth:clerk', 'throttle:api']],
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );

        // These represent expected, already-handled client-error conditions
        // (invalid/expired tokens, bad webhook signatures, insufficient
        // balance, payment/ownership conflicts, party capacity/status/
        // membership rules) with proper HTTP responses registered below —
        // not server failures worth reporting, the same way Laravel
        // excludes AuthenticationException/ValidationException/etc. by
        // default.
        $exceptions->dontReport([
            InvalidClerkTokenException::class,
            InvalidClerkWebhookException::class,
            InsufficientWalletBalanceException::class,
            IdempotencyKeyConflictException::class,
            PackAlreadyOwnedException::class,
            PaymentDeclinedException::class,
            PartyFullException::class,
            PartyNotJoinableException::class,
            PartyHostCannotLeaveException::class,
            InvalidPartyTransitionException::class,
            DuplicateFriendRequestException::class,
            AlreadyFriendsException::class,
            InvalidFriendshipTransitionException::class,
        ]);

        ApiExceptionRegistrar::register($exceptions);
    })
    ->create();
