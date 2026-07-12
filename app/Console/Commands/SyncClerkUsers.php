<?php

namespace App\Console\Commands;

use App\Services\Clerk\ClerkUserSynchronizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;

#[Signature('clerk:sync-users')]
#[Description('Backfill local users from every user currently in Clerk, via the Backend API.')]
class SyncClerkUsers extends Command
{
    private const PAGE_SIZE = 500;

    /**
     * Execute the console command.
     */
    public function handle(ClerkUserSynchronizer $synchronizer): int
    {
        $secretKey = config('services.clerk.secret_key');

        if (! $secretKey) {
            $this->error('CLERK_SECRET_KEY is not configured.');

            return self::FAILURE;
        }

        $synced = 0;
        $offset = 0;

        do {
            $response = Http::withToken($secretKey)
                ->get('https://api.clerk.com/v1/users', [
                    'limit' => self::PAGE_SIZE,
                    'offset' => $offset,
                ]);

            if ($response->failed()) {
                $this->error("Clerk API request failed: {$response->status()} {$response->body()}");

                return self::FAILURE;
            }

            $body = $response->json();
            $users = array_is_list($body) ? $body : Arr::get($body, 'data', []);

            foreach ($users as $userData) {
                $synchronizer->sync($userData);
                $synced++;
            }

            $offset += self::PAGE_SIZE;
        } while (count($users) === self::PAGE_SIZE);

        $this->info("Synced {$synced} user(s) from Clerk.");

        return self::SUCCESS;
    }
}
