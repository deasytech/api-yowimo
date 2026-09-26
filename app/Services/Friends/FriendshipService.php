<?php

namespace App\Services\Friends;

use App\Enums\FriendshipStatus;
use App\Events\FriendRequestAccepted;
use App\Events\FriendRequestSent;
use App\Exceptions\Api\AlreadyFriendsException;
use App\Exceptions\Api\DuplicateFriendRequestException;
use App\Exceptions\Api\InvalidFriendshipTransitionException;
use App\Exceptions\Api\UserBlockedException;
use App\Models\BlockedUser;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class FriendshipService
{
    /**
     * @throws DuplicateFriendRequestException if a pending request already exists between the two users, in either direction.
     * @throws AlreadyFriendsException if the two users are already friends.
     * @throws UserBlockedException if either user has blocked the other.
     */
    public function send(User $sender, User $receiver): Friendship
    {
        if (BlockedUser::query()->betweenUsers($sender, $receiver)->exists()) {
            throw new UserBlockedException;
        }

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

    /**
     * The current pending or accepted friendship between the two users, in either direction.
     */
    public function between(User $first, User $second): ?Friendship
    {
        return Friendship::query()
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('sender_id', $first->id)->where('receiver_id', $second->id))
                ->orWhere(fn ($q) => $q->where('sender_id', $second->id)->where('receiver_id', $first->id)))
            ->whereIn('status', [FriendshipStatus::Pending, FriendshipStatus::Accepted])
            ->latest('id')
            ->first();
    }

    /**
     * Close every open friendship involving the user — or only the one with
     * `$other`, when given — using the same terminal statuses the manual
     * actions produce: the user's own pending requests become cancelled,
     * requests they received become rejected, and accepted friendships
     * become removed. No events are dispatched, matching reject/cancel/remove.
     */
    public function closeAll(User $user, ?User $other = null): void
    {
        $involving = fn ($query) => $query
            ->where(fn ($q) => $q->where('sender_id', $user->id)->orWhere('receiver_id', $user->id))
            ->when($other, fn ($q) => $q->where(fn ($q) => $q->where('sender_id', $other->id)->orWhere('receiver_id', $other->id)));

        Friendship::query()->tap($involving)
            ->where('status', FriendshipStatus::Pending)
            ->where('sender_id', $user->id)
            ->update(['status' => FriendshipStatus::Cancelled]);

        Friendship::query()->tap($involving)
            ->where('status', FriendshipStatus::Pending)
            ->where('receiver_id', $user->id)
            ->update(['status' => FriendshipStatus::Rejected]);

        Friendship::query()->tap($involving)
            ->where('status', FriendshipStatus::Accepted)
            ->update(['status' => FriendshipStatus::Removed]);
    }
}
