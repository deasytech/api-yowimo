<?php

namespace App\Services\Clerk;

use App\Models\User;
use Illuminate\Support\Arr;

class ClerkUserSynchronizer
{
    /**
     * Upsert a local user from a Clerk "User" object, as found in both
     * webhook payloads (`data`) and Backend API list/get responses.
     *
     * @param  array<string, mixed>  $data
     */
    public function sync(array $data): ?User
    {
        $clerkUserId = Arr::get($data, 'id');

        if (! $clerkUserId) {
            return null;
        }

        return User::query()->updateOrCreate(
            ['clerk_user_id' => $clerkUserId],
            array_filter([
                'email' => $this->primaryEmail($data),
                'username' => Arr::get($data, 'username'),
                'first_name' => Arr::get($data, 'first_name'),
                'last_name' => Arr::get($data, 'last_name'),
                'avatar_url' => Arr::get($data, 'image_url'),
            ], fn ($value) => $value !== null),
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function primaryEmail(array $data): ?string
    {
        $primaryId = Arr::get($data, 'primary_email_address_id');
        $addresses = Arr::get($data, 'email_addresses', []);

        foreach ($addresses as $address) {
            if (Arr::get($address, 'id') === $primaryId) {
                return Arr::get($address, 'email_address');
            }
        }

        return Arr::get($addresses, '0.email_address');
    }
}
