<?php

namespace App\Support;

use App\Exceptions\Api\AlreadyFriendsException;
use App\Exceptions\Api\DuplicateFriendRequestException;
use App\Exceptions\Api\GameSessionAlreadyActiveException;
use App\Exceptions\Api\GameSessionNotActiveException;
use App\Exceptions\Api\GameSessionPackUnavailableException;
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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class ApiExceptionRegistrar
{
    /**
     * Register API exception handlers.
     */
    public static function register(Exceptions $exceptions): void
    {
        self::registerHandler(
            $exceptions,
            InvalidClerkTokenException::class,
            fn (InvalidClerkTokenException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 401
            )
        );

        self::registerHandler(
            $exceptions,
            InvalidClerkWebhookException::class,
            fn (InvalidClerkWebhookException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 400
            )
        );

        self::registerHandler(
            $exceptions,
            InsufficientWalletBalanceException::class,
            fn (InsufficientWalletBalanceException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 422
            )
        );

        self::registerHandler(
            $exceptions,
            PackAlreadyOwnedException::class,
            fn (PackAlreadyOwnedException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 409
            )
        );

        self::registerHandler(
            $exceptions,
            IdempotencyKeyConflictException::class,
            fn (IdempotencyKeyConflictException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 409
            )
        );

        self::registerHandler(
            $exceptions,
            PaymentDeclinedException::class,
            fn (PaymentDeclinedException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 402
            )
        );

        self::registerHandler(
            $exceptions,
            PartyFullException::class,
            fn (PartyFullException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 409
            )
        );

        self::registerHandler(
            $exceptions,
            PartyNotJoinableException::class,
            fn (PartyNotJoinableException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 422
            )
        );

        self::registerHandler(
            $exceptions,
            PartyHostCannotLeaveException::class,
            fn (PartyHostCannotLeaveException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 409
            )
        );

        self::registerHandler(
            $exceptions,
            InvalidPartyTransitionException::class,
            fn (InvalidPartyTransitionException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 422
            )
        );

        self::registerHandler(
            $exceptions,
            DuplicateFriendRequestException::class,
            fn (DuplicateFriendRequestException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 409
            )
        );

        self::registerHandler(
            $exceptions,
            AlreadyFriendsException::class,
            fn (AlreadyFriendsException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 409
            )
        );

        self::registerHandler(
            $exceptions,
            InvalidFriendshipTransitionException::class,
            fn (InvalidFriendshipTransitionException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 422
            )
        );

        self::registerHandler(
            $exceptions,
            GameSessionAlreadyActiveException::class,
            fn (GameSessionAlreadyActiveException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 409
            )
        );

        self::registerHandler(
            $exceptions,
            GameSessionNotActiveException::class,
            fn (GameSessionNotActiveException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 422
            )
        );

        self::registerHandler(
            $exceptions,
            GameSessionPackUnavailableException::class,
            fn (GameSessionPackUnavailableException $e) => ApiResponse::error(
                $e->getMessage(),
                status: 422
            )
        );

        self::registerHandler(
            $exceptions,
            ThrottleRequestsException::class,
            fn () => ApiResponse::error(
                'Too many requests.',
                status: 429
            )
        );

        self::registerHandler(
            $exceptions,
            AuthenticationException::class,
            fn () => ApiResponse::error(
                'Unauthenticated.',
                status: 401
            )
        );

        self::registerHandler(
            $exceptions,
            AuthorizationException::class,
            fn (AuthorizationException $e) => ApiResponse::error(
                $e->getMessage() ?: 'This action is unauthorized.',
                status: 403
            )
        );

        self::registerHandler(
            $exceptions,
            ValidationException::class,
            fn (ValidationException $e) => ApiResponse::error(
                'Validation failed',
                $e->errors(),
                status: 422
            )
        );

        self::registerHandler(
            $exceptions,
            ModelNotFoundException::class,
            fn () => ApiResponse::error(
                'Resource not found.',
                status: 404
            )
        );

        self::registerHandler(
            $exceptions,
            NotFoundHttpException::class,
            fn () => ApiResponse::error(
                'Resource not found.',
                status: 404
            )
        );

        self::registerHandler(
            $exceptions,
            HttpException::class,
            fn (HttpException $e) => ApiResponse::error(
                $e->getMessage(),
                status: $e->getStatusCode()
            )
        );

        self::registerHandler(
            $exceptions,
            Throwable::class,
            function () {
                if (app()->hasDebugModeEnabled()) {
                    return null;
                }

                return ApiResponse::error(
                    'Something went wrong.',
                    status: 500
                );
            }
        );
    }

    /**
     * Register a single API exception handler.
     */
    private static function registerHandler(
        Exceptions $exceptions,
        string $exceptionClass,
        callable $handler
    ): void {
        $exceptions->render(function (Throwable $exception, Request $request) use (
            $exceptionClass,
            $handler
        ) {
            if (! $request->is('api/*')) {
                return null;
            }

            if (! $exception instanceof $exceptionClass) {
                return null;
            }

            return $handler($exception);
        });
    }
}
