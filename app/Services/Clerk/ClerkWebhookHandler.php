<?php

namespace App\Services\Clerk;

use App\Enums\UserStatus;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;

class ClerkWebhookHandler
{
    public function __construct(private readonly ClerkUserSynchronizer $synchronizer) {}

    /**
     * Handle a verified Clerk webhook payload, idempotently.
     *
     * @param  array<string, mixed>  $payload
     */
    public function handle(string $eventId, array $payload): void
    {
        if (WebhookEvent::query()->where('event_id', $eventId)->exists()) {
            return;
        }

        $type = Arr::get($payload, 'type', '');
        $data = Arr::get($payload, 'data', []);

        match ($type) {
            'user.created', 'user.updated' => $this->synchronizer->sync($data),
            'user.deleted' => $this->deactivateUser($data),
            default => null,
        };

        try {
            WebhookEvent::query()->create([
                'provider' => 'clerk',
                'event_id' => $eventId,
                'event_type' => $type,
                'payload' => $payload,
                'processed_at' => now(),
            ]);
        } catch (QueryException) {
            // Another delivery of the same event won the race on the unique
            // event_id constraint; the effect above has already happened once.
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function deactivateUser(array $data): void
    {
        $clerkUserId = Arr::get($data, 'id');

        $user = User::withTrashed()->where('clerk_user_id', $clerkUserId)->first();

        if (! $user) {
            return;
        }

        $user->forceFill([
            'status' => UserStatus::Deactivated,
            'deleted_at' => $user->deleted_at ?? now(),
        ])->save();
    }
}
