<?php

namespace App\Models;

use App\Enums\JoinMode;
use App\Enums\PartyMemberStatus;
use Database\Factories\PartyMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'party_id',
    'user_id',
    'guest_name',
    'guest_emoji',
    'join_mode',
    'status',
    'is_ready',
    'joined_at',
    'left_at',
])]
class PartyMember extends Model
{
    /** @use HasFactory<PartyMemberFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => PartyMemberStatus::class,
            'join_mode' => JoinMode::class,
            'is_ready' => 'boolean',
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    /**
     * A pass-and-play / in-room player added by the host, with no account
     * of their own — has guest_name/guest_emoji instead of a user_id.
     */
    public function isGuest(): bool
    {
        return $this->user_id === null;
    }

    /**
     * @return BelongsTo<Party, $this>
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
