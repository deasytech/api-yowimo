<?php

namespace App\Services\Friends;

use App\Enums\FriendshipStatus;
use App\Events\FriendRequestAccepted;
use App\Events\FriendRequestSent;
use App\Exceptions\Api\AlreadyFriendsException;
use App\Exceptions\Api\DuplicateFriendRequestException;
use App\Exceptions\Api\InvalidFriendshipTransitionException;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FriendshipService
{
    /**
     * @throws DuplicateFriendRequestException if a pending request already exists between the two users, in either direction.
     * @throws AlreadyFriendsException if the two users are already friends.
     */
    public function send(User $sender, User $receiver): Friendship
    {
        return DB::transaction(function () use ($sender, $receiver) {
            $existing = Friendship::query()
                ->where(fn ($q) => $q->where('sender_id', $sender->id)->where('receiver_id', $receiver->id))
                ->orWhere(fn ($q) => $q->where('sender_id', $receiver->id)->where('receiver_id', $sender->id))
                ->whereIn('status', [FriendshipStatus::Pending, FriendshipStatus::Accepted])
                ->lockForUpdate()
                ->first();

            if ($existing?->status === FriendshipStatus::Accepted) {
                throw new AlreadyFriendsException;
            }

            if ($existing?->status === FriendshipStatus::Pending) {
                throw new DuplicateFriendRequestException;
            }

            $friendship = Friendship::create([
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'status' => FriendshipStatus::Pending,
            ]);

            FriendRequestSent::dispatch($friendship->id, $sender->id, $receiver->id);

            return $friendship;
        });
    }

    /**
     * @throws InvalidFriendshipTransitionException if the request is not pending.
     */
    public function accept(Friendship $friendship): Friendship
    {
        if ($friendship->status !== FriendshipStatus::Pending) {
            throw new InvalidFriendshipTransitionException('This friend request can no longer be accepted.');
        }

        $friendship->update([
            'status' => FriendshipStatus::Accepted,
            'accepted_at' => now(),
        ]);

        FriendRequestAccepted::dispatch($friendship->id, $friendship->sender_id, $friendship->receiver_id);

        return $friendship->refresh();
    }

    /**
     * @throws InvalidFriendshipTransitionException if the request is not pending.
     */
    public function reject(Friendship $friendship): Friendship
    {
        if ($friendship->status !== FriendshipStatus::Pending) {
            throw new InvalidFriendshipTransitionException('This friend request can no longer be rejected.');
        }

        $friendship->update(['status' => FriendshipStatus::Rejected]);

        return $friendship->refresh();
    }

    /**
     * @throws InvalidFriendshipTransitionException if the request is not pending.
     */
    public function cancel(Friendship $friendship): Friendship
    {
        if ($friendship->status !== FriendshipStatus::Pending) {
            throw new InvalidFriendshipTransitionException('This friend request can no longer be cancelled.');
        }

        $friendship->update(['status' => FriendshipStatus::Cancelled]);

        return $friendship->refresh();
    }

    /**
     * @throws InvalidFriendshipTransitionException if the friendship is not currently accepted.
     */
    public function remove(Friendship $friendship): Friendship
    {
        if ($friendship->status !== FriendshipStatus::Accepted) {
            throw new InvalidFriendshipTransitionException('This friendship cannot be removed from its current status.');
        }

        $friendship->update(['status' => FriendshipStatus::Removed]);

        return $friendship->refresh();
    }

    /**
     * Accepted friendships involving the user, either as sender or receiver.
     *
     * @return Collection<int, Friendship>
     */
    public function friends(User $user): Collection
    {
        return Friendship::query()
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->where('status', FriendshipStatus::Accepted)
            ->with(['sender', 'receiver'])
            ->orderByDesc('accepted_at')
            ->get();
    }

    /**
     * Pending friend requests involving the user, either incoming or outgoing.
     *
     * @return Collection<int, Friendship>
     */
    public function pendingRequests(User $user): Collection
    {
        return Friendship::query()
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->where('status', FriendshipStatus::Pending)
            ->with(['sender', 'receiver'])
            ->orderByDesc('created_at')
            ->get();
    }
}
