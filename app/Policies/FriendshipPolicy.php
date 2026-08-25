<?php

namespace App\Policies;

use App\Models\Friendship;
use App\Models\User;

class FriendshipPolicy
{
    /**
     * Determine whether the user can view their own friends/pending requests lists.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can send a friend request.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can accept the friend request. Receiver-only.
     */
    public function accept(User $user, Friendship $friendship): bool
    {
        return $friendship->receiver_id === $user->id;
    }

    /**
     * Determine whether the user can reject the friend request. Receiver-only.
     */
    public function reject(User $user, Friendship $friendship): bool
    {
        return $friendship->receiver_id === $user->id;
    }

    /**
     * Determine whether the user can cancel their own pending outgoing request. Sender-only.
     */
    public function cancel(User $user, Friendship $friendship): bool
    {
        return $friendship->sender_id === $user->id;
    }

    /**
     * Determine whether the user can remove (unfriend) the friendship. Either side.
     */
    public function remove(User $user, Friendship $friendship): bool
    {
        return $friendship->sender_id === $user->id || $friendship->receiver_id === $user->id;
    }
}
