<?php

namespace App\Support;

use App\Exceptions\Api\InvalidClerkTokenException;
use App\Exceptions\Api\InvalidClerkWebhookException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        $exceptions->render(function ($exception, Request $request) use (
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
