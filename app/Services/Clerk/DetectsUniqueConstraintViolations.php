<?php

namespace App\Services\Clerk;

use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

/**
 * Shared by ClerkUserProvisioner and ClerkUserSynchronizer, which both need
 * to tell a specific column's unique-constraint violation apart from any
 * other QueryException, to decide whether a generated fallback value (a
 * username or referral code) is safe to regenerate and retry.
 */
trait DetectsUniqueConstraintViolations
{
    /**
     * @param  array<int, string>  $needles
     */
    private function isUniqueConstraintViolation(QueryException $exception, array $needles): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $matchesColumn = Str::contains($message, $needles);
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $matchesColumn && $isUniqueViolation;
    }
}
