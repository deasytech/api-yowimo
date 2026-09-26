<?php

namespace App\Services\Clerk;

use App\Exceptions\Api\AccountDeletionFailedException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Lean HTTP-facade wrapper around the Clerk Backend API (no SDK package),
 * authenticated with CLERK_SECRET_KEY — the same key and base URL
 * SyncClerkUsers uses.
 */
class ClerkBackendClient
{
    private const BASE_URL = 'https://api.clerk.com/v1';

    /**
     * Delete the Clerk user. A 404 counts as success: the user is already
     * gone from Clerk, which is the state this call exists to reach.
     *
     * @throws AccountDeletionFailedException
     */
    public function deleteUser(string $clerkUserId): void
    {
        $secretKey = config('services.clerk.secret_key');

        if (! $secretKey) {
            Log::error('Clerk user deletion attempted without CLERK_SECRET_KEY configured.');

            throw new AccountDeletionFailedException;
        }

        try {
            $response = Http::withToken($secretKey)
                ->timeout(10)
                ->delete(self::BASE_URL.'/users/'.rawurlencode($clerkUserId));
        } catch (ConnectionException $e) {
            Log::error('Clerk user deletion failed to connect.', ['exception' => $e->getMessage()]);

            throw new AccountDeletionFailedException;
        }

        if ($response->successful() || $response->notFound()) {
            return;
        }

        Log::error('Clerk user deletion was rejected.', ['status' => $response->status(), 'body' => $response->body()]);

        throw new AccountDeletionFailedException;
    }
}
