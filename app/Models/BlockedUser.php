<?php

namespace App\Models;

use Database\Factories\BlockedUserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'blocker_id',
    'blocked_id',
])]
class BlockedUser extends Model
{
    /** @use HasFactory<BlockedUserFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function blocker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocker_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function blocked(): BelongsTo
    {
        return $this->belongsTo(User::class, 'blocked_id');
    }

    /**
     * Blocks between the two users, in either direction.
     *
     * @param  Builder<BlockedUser>  $query
     */
    #[Scope]
    protected function betweenUsers(Builder $query, User $first, User $second): void
    {
        $query->where(fn ($q) => $q
            ->where(fn ($q) => $q->where('blocker_id', $first->id)->where('blocked_id', $second->id))
            ->orWhere(fn ($q) => $q->where('blocker_id', $second->id)->where('blocked_id', $first->id)));
    }
}
