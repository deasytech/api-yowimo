<?php

namespace App\Services\Friends;

use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BlockService
{
    public function __construct(private readonly FriendshipService $friendships) {}

    /**
     * Block `$blocked` on behalf of `$blocker`, idempotently. Any pending or
     * accepted friendship between the two is closed in the same transaction.
     * The block is social-only: parties and gameplay are unaffected.
     */
    public function block(User $blocker, User $blocked): BlockedUser
    {
        return DB::transaction(function () use ($blocker, $blocked) {
            // Same ordered user-row lock as FriendshipService::send(), so a
            // block and a request between the same pair can't interleave.
            User::query()->whereKey([$blocker->id, $blocked->id])->orderBy('id')->lockForUpdate()->get();

            $block = BlockedUser::query()->firstOrCreate([
                'blocker_id' => $blocker->id,
                'blocked_id' => $blocked->id,
            ]);

            $this->friendships->closeAll($blocker, $blocked);

            return $block;
        });
    }

    /**
     * Remove `$blocker`'s block on `$blocked`, if any. Closed friendships are not restored.
     */
    public function unblock(User $blocker, User $blocked): void
    {
        BlockedUser::query()
            ->where('blocker_id', $blocker->id)
            ->where('blocked_id', $blocked->id)
            ->delete();
    }

    /**
     * Users the given user has blocked, newest first.
     *
     * @return Collection<int, BlockedUser>
     */
    public function blockedBy(User $user): Collection
    {
        return BlockedUser::query()
            ->where('blocker_id', $user->id)
            ->whereHas('blocked')
            ->with('blocked')
            ->latest('id')
            ->get();
    }

    public function isBlockedEitherWay(User $first, User $second): bool
    {
        return BlockedUser::query()->betweenUsers($first, $second)->exists();
    }
}
