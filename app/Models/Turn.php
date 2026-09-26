<?php

namespace App\Models;

use Database\Factories\TurnFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'game_session_id',
    'round_id',
    'user_id',
    'pack_card_id',
    'position',
    'started_at',
    'expires_at',
    'completed_at',
    'is_afk',
    'is_skipped',
])]
class Turn extends Model
{
    /** @use HasFactory<TurnFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
            'is_afk' => 'boolean',
            'is_skipped' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<GameSession, $this>
     */
    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    /**
     * @return BelongsTo<Round, $this>
     */
    public function round(): BelongsTo
    {
        return $this->belongsTo(Round::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<PackCard, $this>
     */
    public function packCard(): BelongsTo
    {
        return $this->belongsTo(PackCard::class);
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }
}
