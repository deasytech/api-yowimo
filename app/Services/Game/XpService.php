<?php

namespace App\Services\Game;

use App\Enums\XpTransactionType;
use App\Exceptions\Api\IdempotencyKeyConflictException;
use App\Models\User;
use App\Models\XpTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class XpService
{
    /**
     * Credit XP to a user (turn votes, challenge completion, MVP bonus).
     */
    public function credit(
        User $user,
        int $amount,
        XpTransactionType $type,
        ?Model $reference = null,
        ?int $gameSessionId = null,
        ?string $idempotencyKey = null,
    ): XpTransaction {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Credit amount must be positive.');
        }

        return DB::transaction(function () use ($user, $amount, $type, $reference, $gameSessionId, $idempotencyKey) {
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            try {
                $transaction = XpTransaction::create([
                    'user_id' => $lockedUser->id,
                    'type' => $type,
                    'amount' => $amount,
                    'game_session_id' => $gameSessionId,
                    'reference_type' => $reference?->getMorphClass(),
                    'reference_id' => $reference?->getKey(),
                    'idempotency_key' => $idempotencyKey,
                ]);
            } catch (QueryException $exception) {
                if ($idempotencyKey === null || ! $this->isIdempotencyKeyUniqueViolation($exception)) {
                    throw $exception;
                }

                // Concurrent retry of the same operation; return the entry that
                // won the race instead of applying it twice. Scoped to this user,
                // since the unique constraint is per-user.
                $existing = XpTransaction::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('idempotency_key', $idempotencyKey)
                    ->firstOrFail();

                if ($existing->reference_type !== $reference?->getMorphClass()
                    || (string) $existing->reference_id !== (string) $reference?->getKey()) {
                    throw new IdempotencyKeyConflictException;
                }

                return $existing;
            }

            $lockedUser->update(['xp' => $lockedUser->xp + $amount]);

            return $transaction;
        });
    }

    private function isIdempotencyKeyUniqueViolation(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());
        $sqlState = (string) $exception->getCode();

        $matchesColumn = Str::contains($message, 'idempotency_key');
        $isUniqueViolation = Str::contains($message, ['unique', 'duplicate']) || in_array($sqlState, ['23000', '23505'], true);

        return $matchesColumn && $isUniqueViolation;
    }
}
