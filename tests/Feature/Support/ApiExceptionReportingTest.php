<?php

use App\Exceptions\Api\AccountDeletionFailedException;
use App\Exceptions\Api\GameSessionAlreadyActiveException;
use App\Exceptions\Api\GameSessionNotActiveException;
use App\Exceptions\Api\GameSessionPackUnavailableException;
use App\Exceptions\Api\PackNotInGameTypeException;
use App\Exceptions\Api\PartyGameAlreadyStartedException;
use App\Exceptions\Api\TurnNotActiveException;
use App\Exceptions\Api\UserBlockedException;
use Illuminate\Support\Facades\Exceptions;

/*
|--------------------------------------------------------------------------
| Expected client errors stay out of the logs
|--------------------------------------------------------------------------
|
| Handler::report() is what writes the ERROR entry (and notifies Sentry)
| for an exception, so the dontReport() list in bootstrap/app.php is
| asserted class by class here: every API exception the controllers
| translate into a 4xx is an expected client condition, not a server
| failure. Anything upstream-facing (5xx) must keep being reported.
*/

it('does not report an expected API client error', function (string $exceptionClass) {
    expect(Exceptions::shouldReport(new $exceptionClass))->toBeFalse();
})->with([
    GameSessionAlreadyActiveException::class,
    GameSessionNotActiveException::class,
    GameSessionPackUnavailableException::class,
    PackNotInGameTypeException::class,
    PartyGameAlreadyStartedException::class,
    TurnNotActiveException::class,
    UserBlockedException::class,
]);

it('keeps reporting the upstream failures a client cannot fix', function () {
    expect(Exceptions::shouldReport(new AccountDeletionFailedException))->toBeTrue();
});
